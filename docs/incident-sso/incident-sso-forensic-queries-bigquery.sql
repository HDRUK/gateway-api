-- Forensic queries for docs/incident-sso-account-confusion.md
-- Dialect: GoogleSQL (BigQuery). Read-only.
--
-- The `audit_logs` MySQL table is empty by design: App\Auditor\Auditor publishes
-- to Pub/Sub, never to the database. The trail lives here:
--
--   project      hdrinnovationgatewayprod            (region europe-west1)
--   topic        gateway-audit-logs-topic
--   subscription gateway-audit-logs-bq-subscription  (BigQuery sink, use_table_schema)
--   table        hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod
--   dead letter  gateway-audit-logs-topic-deadletter (10-day retention, 10 attempts)
--
-- Defined in gcp-multi-project-infrastructure/hdrinnovationgatewayprod/{13-bigquery,14-pubsub}.tf
-- Preprod and dev mirror this with _preprod / _dev suffixes in their own projects.
--
-- Schema: created_at TIMESTAMP, user_id INT64, team_id INT64, target_user_id INT64,
--         target_team_id INT64, description STRING, action_type STRING,
--         action_name STRING, action_service STRING.
--
-- The table is NOT partitioned, so every query below is a full scan. Dry-run
-- first (bq query --dry_run) and consider --maximum_bytes_billed.
--
-- Nothing here creates tables or writes anything. Sections 0-3, 5 and 6 use the
-- script variables below, so run the file as one BigQuery script (the DECLAREs
-- must precede every other statement). Section 4 is written with the values
-- inlined, so those queries can also be pasted into the console one at a time.

DECLARE suspect INT64 DEFAULT 2;
DECLARE bug_introduced TIMESTAMP DEFAULT TIMESTAMP '2024-10-01 00:00:00+00';

-- ---------------------------------------------------------------------------
-- 0. Sanity: does the sink hold the incident window, and are timestamps sane?
-- ---------------------------------------------------------------------------
-- Auditor sets created_at = gettimeofday(true) * 1000000 — a PHP float of epoch
-- microseconds, JSON-encoded. If that ever serialised as scientific notation the
-- Pub/Sub sink would have rejected the row to the dead-letter topic rather than
-- writing it. Check both ends: plausible min/max here, and the dead-letter depth
-- (see the gcloud block at the bottom of this file).
SELECT COUNT(*)        AS rows_total,
       MIN(created_at) AS earliest,
       MAX(created_at) AS latest,
       COUNTIF(created_at IS NULL) AS null_timestamps
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`;

SELECT action_type, COUNT(*) AS n, MIN(created_at) AS first_seen, MAX(created_at) AS last_seen
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
GROUP  BY action_type
ORDER  BY n DESC;

-- ---------------------------------------------------------------------------
-- 1. Misrouted logins: how many, and how far back
-- ---------------------------------------------------------------------------
-- LOGIN rows carry target_user_id = the resolved (wrong) user, user_id = -99,
-- action_name lowercased to 'sociallogincontroller@callback' (or '@dtacallback'),
-- description 'User <id> with login through <provider> has been connected'.
-- The provider is only in the description text, hence the LIKE matching.
SELECT DATE(created_at) AS day,
       COUNT(*)         AS logins,
       MIN(created_at)  AS first_of_day,
       MAX(created_at)  AS last_of_day
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'LOGIN'
  AND  target_user_id = suspect
GROUP  BY day
ORDER  BY day;

-- What provider string each login recorded. CAUTION: this is $user->provider —
-- the provider column of the account the login RESOLVED TO, not the provider
-- used to log in. updateUser() leaves that column untouched on the open-athens
-- path, so a misrouted login reports whatever user_id 2 already said. Read this
-- as "what the account looked like", not "how they logged in".
SELECT CASE
         WHEN description LIKE '%open-athens%' THEN 'open-athens'
         WHEN description LIKE '%registry%'    THEN 'registry'
         WHEN description LIKE '%google%'      THEN 'google'
         WHEN description LIKE '%linkedin%'    THEN 'linkedin'
         WHEN description LIKE '%azure%'       THEN 'azure'
         ELSE 'other/unknown'
       END             AS provider_recorded_on_account,
       COUNT(*)        AS logins,
       MIN(created_at) AS first_seen,
       MAX(created_at) AS last_seen
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'LOGIN'
  AND  target_user_id = suspect
GROUP  BY provider_recorded_on_account
ORDER  BY logins DESC;

-- Not just the suspect: every account logins resolved to, ranked. Cross-check
-- the ids against the `providerid IS NULL` candidate set from the MySQL pack —
-- anything in both lists is a second sink account. Not filtered by provider,
-- for the reason given above; the description is returned so you can see what
-- each group recorded.
SELECT target_user_id,
       COUNT(*)                   AS logins,
       COUNT(DISTINCT description) AS distinct_descriptions,
       ANY_VALUE(description)     AS sample_description,
       MIN(created_at)            AS first_seen,
       MAX(created_at)            AS last_seen
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'LOGIN'
GROUP  BY target_user_id
ORDER  BY logins DESC;

-- Raw timeline of every misrouted login.
SELECT created_at, action_name, action_service, description
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'LOGIN'
  AND  target_user_id = suspect
ORDER  BY created_at;

-- ---------------------------------------------------------------------------
-- 2. Profile overwrites (the name/email churn)
-- ---------------------------------------------------------------------------
-- UserController@update rows carry user_id = the poisoned JWT id and
-- description 'User <id> updated'. They do NOT record the values written.
SELECT DATE(created_at) AS day, COUNT(*) AS overwrites
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'UPDATE'
  AND  action_name LIKE 'usercontroller@update%'
  AND  (user_id = suspect OR description = CONCAT('User ', CAST(suspect AS STRING), ' updated'))
GROUP  BY day
ORDER  BY day;

-- ---------------------------------------------------------------------------
-- 3. Pair each misrouted login with the profile sync that followed it.
--    One pair ~= one distinct person misrouted into the account.
-- ---------------------------------------------------------------------------
WITH logins AS (
  SELECT created_at, description
  FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
  WHERE  action_type = 'LOGIN' AND target_user_id = suspect
), updates AS (
  SELECT created_at
  FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
  WHERE  action_type = 'UPDATE'
    AND  action_name LIKE 'usercontroller@update%'
    AND  user_id = suspect
)
SELECT l.created_at                                        AS login_at,
       MIN(u.created_at)                                   AS overwrite_at,
       TIMESTAMP_DIFF(MIN(u.created_at), l.created_at, SECOND) AS seconds_after_login,
       ANY_VALUE(l.description)                            AS login_description
FROM   logins l
LEFT   JOIN updates u
       ON u.created_at BETWEEN l.created_at
                           AND TIMESTAMP_ADD(l.created_at, INTERVAL 10 MINUTE)
GROUP  BY l.created_at
ORDER  BY l.created_at;

-- Distinct misroute episodes, collapsing repeat logins in one sitting: count
-- gaps of more than an hour between consecutive logins as separate people.
WITH logins AS (
  SELECT created_at,
         TIMESTAMP_DIFF(created_at,
                        LAG(created_at) OVER (ORDER BY created_at),
                        MINUTE) AS gap_minutes
  FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
  WHERE  action_type = 'LOGIN' AND target_user_id = suspect
)
SELECT COUNTIF(gap_minutes IS NULL OR gap_minutes > 60) AS distinct_episodes,
       COUNT(*)                                         AS total_logins
FROM   logins;

-- ---------------------------------------------------------------------------
-- 4. The bad login sessions, and what they did
-- ---------------------------------------------------------------------------
-- `user_id = 2` on its own is NOT the hijacked set: it also holds the service
-- account's own legitimate traffic, which predates this bug. Activity only
-- counts as hijacked if it falls inside a window opened by a misrouted login.
--
-- Window = [login_at, login_at + 86400s), truncated by the next misrouted
-- login. 86400s is JWT_EXPIRATION (config/jwt.php:6) — how long the poisoned
-- token stays valid. Truncating at the next login is an attribution choice, not
-- a security one: the earlier token stays valid until it expires, but activity
-- after a newer login is attributed to that newer session.
--
-- Windows are seeded from EVERY login that resolved to this account, not just
-- ones whose description says open-athens. The description interpolates
-- $user->provider — the resolved account's provider column, not the login
-- provider — and updateUser() leaves that column untouched on the open-athens
-- path, so a misrouted login records whatever the account already said.
-- Filtering on 'open-athens' would match only correctly-resolved logins.
--
-- Every query below is standalone — no temp tables, no script variables. The
-- account id appears as the literal 2 and the token lifetime as 86400; change
-- them in each query if you investigate a different account.

-- One row per bad login session: when it started, how long it stayed active,
-- and how much it touched. This is the headline table for the incident record.
WITH sessions AS (
  SELECT ROW_NUMBER() OVER (ORDER BY login_at) AS session_no,
         login_at,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at AS login_at
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN' AND target_user_id = 2
  )
)
SELECT s.session_no,
       s.login_at,
       MAX(a.created_at)                                     AS last_activity,
       TIMESTAMP_DIFF(MAX(a.created_at), s.login_at, MINUTE) AS active_minutes,
       COUNT(a.created_at)                                   AS events,
       COUNTIF(a.action_type IN ('CREATE','UPDATE','DELETE')) AS writes,
       COUNTIF(a.action_type = 'GET'
               AND (a.action_name LIKE '%cohort%'
                 OR a.action_name LIKE '%dataaccess%'
                 OR a.action_name LIKE '%user%'))            AS pii_reads,
       COUNTIF(a.action_name LIKE '%role%'
            OR a.action_name LIKE '%permission%'
            OR a.action_name LIKE '%team%')                  AS role_or_team_actions
FROM   sessions s
LEFT   JOIN `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
       ON a.user_id    = 2
      AND a.created_at >= s.login_at
      AND a.created_at <  s.session_end
GROUP  BY s.session_no, s.login_at
ORDER  BY s.session_no;

-- Every audit row belonging to a bad session, in order. This is the "what they
-- did" record — read it alongside the summary above.
WITH sessions AS (
  SELECT ROW_NUMBER() OVER (ORDER BY login_at) AS session_no,
         login_at,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at AS login_at
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN' AND target_user_id = 2
  )
)
SELECT s.session_no,
       s.login_at,
       a.created_at,
       TIMESTAMP_DIFF(a.created_at, s.login_at, SECOND) AS seconds_into_session,
       a.action_type, a.action_name,
       a.team_id, a.target_user_id, a.target_team_id, a.description
FROM   sessions s
JOIN   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
       ON a.user_id    = 2
      AND a.created_at >= s.login_at
      AND a.created_at <  s.session_end
ORDER  BY s.session_no, a.created_at;

-- What bad sessions did, aggregated by action — the shape of the exposure.
WITH sessions AS (
  SELECT login_at,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at AS login_at
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN' AND target_user_id = 2
  )
)
SELECT a.action_type, a.action_name,
       COUNT(*)                    AS n,
       COUNT(DISTINCT s.login_at)  AS sessions,
       MIN(a.created_at)           AS first_seen,
       MAX(a.created_at)           AS last_seen
FROM   sessions s
JOIN   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
       ON a.user_id    = 2
      AND a.created_at >= s.login_at
      AND a.created_at <  s.session_end
GROUP  BY a.action_type, a.action_name
ORDER  BY n DESC;

-- Writes made under a bad session — the data-integrity surface. Includes the
-- profile overwrites and any team, role or access-request mutation.
WITH sessions AS (
  SELECT ROW_NUMBER() OVER (ORDER BY login_at) AS session_no,
         login_at,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at AS login_at
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN' AND target_user_id = 2
  )
)
SELECT s.session_no, s.login_at, a.created_at,
       a.action_type, a.action_name,
       a.team_id, a.target_user_id, a.target_team_id, a.description
FROM   sessions s
JOIN   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
       ON a.user_id    = 2
      AND a.created_at >= s.login_at
      AND a.created_at <  s.session_end
WHERE  a.action_type IN ('CREATE', 'UPDATE', 'DELETE')
    OR a.action_name LIKE '%role%'
    OR a.action_name LIKE '%permission%'
    OR a.action_name LIKE '%team%'
ORDER  BY a.created_at;

-- Applicant PII reached under a bad session — Cohort Discovery, DAR and user
-- surfaces. This is the disclosure question the reporter raised.
WITH sessions AS (
  SELECT ROW_NUMBER() OVER (ORDER BY login_at) AS session_no,
         login_at,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at AS login_at
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN' AND target_user_id = 2
  )
)
SELECT s.session_no, s.login_at, a.created_at, a.action_name, a.description
FROM   sessions s
JOIN   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
       ON a.user_id    = 2
      AND a.created_at >= s.login_at
      AND a.created_at <  s.session_end
WHERE  a.action_type = 'GET'
  AND  (a.action_name LIKE '%cohort%' OR a.action_name LIKE '%dataaccess%' OR a.action_name LIKE '%user%')
ORDER  BY a.created_at;

-- The control. Splits this account's entire audit history into activity inside
-- a bad session and activity outside one. Run it before quoting any number
-- above: if almost everything falls inside, the windows are swallowing the
-- account's normal traffic and the scoping needs tightening.
WITH sessions AS (
  SELECT login_at,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at AS login_at
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN' AND target_user_id = 2
  )
)
SELECT COUNTIF(in_session)     AS events_in_bad_sessions,
       COUNTIF(NOT in_session) AS events_outside,
       COUNT(*)                AS events_total
FROM (
  SELECT EXISTS (
           SELECT 1 FROM sessions s
           WHERE a.created_at >= s.login_at AND a.created_at < s.session_end
         ) AS in_session
  FROM `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
  WHERE a.user_id = 2
);

-- ---------------------------------------------------------------------------
-- 5. Scale of the silent failure
-- ---------------------------------------------------------------------------
-- Every login, split by whether it landed on the suspect account. OpenAthens
-- logins cannot be isolated here: the description records the resolved
-- account's provider column, so misroutes do not say 'open-athens'. The
-- per-day totals are therefore all providers combined.
SELECT DATE(created_at)                 AS day,
       COUNT(*)                         AS logins,
       COUNTIF(target_user_id = suspect) AS misrouted,
       COUNT(DISTINCT target_user_id)   AS distinct_accounts_resolved
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'LOGIN'
  AND  created_at >= bug_introduced
GROUP  BY day
ORDER  BY day;

-- SSO exceptions in the same window — malformed-claim and unique-key collisions
-- surface here, and the description carries the raw exception message.
SELECT DATE(created_at) AS day,
       SUBSTR(description, 1, 160) AS sample,
       COUNT(*) AS n
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'EXCEPTION'
  AND  action_name LIKE 'sociallogincontroller@%'
  AND  created_at >= bug_introduced
GROUP  BY day, sample
ORDER  BY day;

-- ---------------------------------------------------------------------------
-- 6. Export the evidence set for the incident record
-- ---------------------------------------------------------------------------
-- Every audit row touching the account, tagged with the bad session it falls
-- inside (session_no NULL = the account's own baseline activity), plus the
-- misrouted logins themselves and the SSO exceptions. One CSV for the record.
WITH sessions AS (
  SELECT ROW_NUMBER() OVER (ORDER BY login_at) AS session_no,
         login_at,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at AS login_at
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN' AND target_user_id = 2
  )
)
SELECT a.created_at, a.user_id, a.team_id, a.target_user_id, a.target_team_id,
       a.action_type, a.action_name, a.action_service, a.description,
       s.session_no, s.login_at
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
LEFT   JOIN sessions s
       ON a.user_id = 2
      AND a.created_at >= s.login_at
      AND a.created_at <  s.session_end
WHERE  a.user_id = 2
   OR  a.target_user_id = 2
   OR  (a.action_name LIKE 'sociallogincontroller@%' AND a.created_at >= TIMESTAMP '2024-10-01 00:00:00+00')
ORDER  BY a.created_at;

-- ---------------------------------------------------------------------------
-- 7. Who was the login MEANT to be? What the trail can and cannot say
-- ---------------------------------------------------------------------------
-- There is no field for it. A LOGIN row's target_user_id is $user->id — the
-- account the lookup RESOLVED TO, i.e. the wrong one. That is why it is the
-- filter in every query above, not the answer. The intended identity only ever
-- existed as the eduPersonTargetedID claim, which is never written to the audit
-- trail; and in a misroute there is usually no intended ACCOUNT at all, because
-- the lookup matched an existing row instead of creating the person's own.
--
-- Three things still carry real identities. In descending order of reliability:

-- (a) Exception messages. A duplicate-key failure logs the raw driver message,
--     which embeds the offending value — so collisions on users_email_unique
--     put a real institutional email address into the audit description. This
--     is the only place the audit trail names a real person.
SELECT created_at,
       action_name,
       REGEXP_EXTRACT(description, r'[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}') AS leaked_email,
       description
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'EXCEPTION'
  AND  action_name LIKE 'sociallogincontroller@%'
  AND  REGEXP_CONTAINS(description, r'[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}')
ORDER  BY created_at;

-- The institutions behind those addresses, and when each was last seen.
SELECT REGEXP_EXTRACT(description, r'@([A-Za-z0-9.\-]+\.[A-Za-z]{2,})') AS institution_domain,
       COUNT(*)        AS occurrences,
       MIN(created_at) AS first_seen,
       MAX(created_at) AS last_seen
FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
WHERE  action_type = 'EXCEPTION'
  AND  action_name LIKE 'sociallogincontroller@%'
  AND  REGEXP_CONTAINS(description, r'@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}')
GROUP  BY institution_domain
ORDER  BY occurrences DESC;

-- (b) The profile sync inside each session. It carries no values, but it dates
--     the moment the account's name/email became that person's — which is what
--     you correlate against HubSpot property history and Cloud Logging.
WITH sessions AS (
  SELECT ROW_NUMBER() OVER (ORDER BY login_at) AS session_no,
         login_at,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at AS login_at
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN' AND target_user_id = 2
  )
)
SELECT s.session_no,
       s.login_at,
       a.created_at                                     AS overwritten_at,
       TIMESTAMP_DIFF(a.created_at, s.login_at, SECOND) AS seconds_into_session,
       a.description
FROM   sessions s
JOIN   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
       ON a.user_id    = 2
      AND a.created_at >= s.login_at
      AND a.created_at <  s.session_end
WHERE  a.action_name LIKE 'usercontroller@update%'
ORDER  BY a.created_at;

-- (c) Not in BigQuery. Use the overwritten_at timestamps from (b) as the index:
--     * HubSpot — UserController::update() calls updateOrCreateContact($id), so
--       the contact tied to users.hubspot_id holds per-property change history.
--       This is the best shot at the full sequence of names and emails.
--     * Cloud Logging — the PUT /users/2 request itself, 30-day retention. See
--       the gcloud block below.
--     * email_verifications (MySQL) — dated secondary addresses profile syncs
--       tried to attach. See docs/incident-sso-forensic-queries.sql section 2.

/*
-- ---------------------------------------------------------------------------
-- Running these, and the two non-BigQuery sources
-- ---------------------------------------------------------------------------

# Dry-run first; the table is unpartitioned.
bq --project_id=hdrinnovationgatewayprod query --use_legacy_sql=false --dry_run \
   "SELECT COUNT(*) FROM \`hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod\`"

# Export an evidence CSV.
bq --project_id=hdrinnovationgatewayprod query --use_legacy_sql=false --format=csv --max_rows=100000 \
   --maximum_bytes_billed=10000000000 "<query 6 above>" > sso-incident-audit.csv


# (a) Dead letter — rows the sink rejected are NOT in the table. Check depth
#     before trusting any count above as complete. 10-day retention only.
gcloud pubsub topics list-subscriptions gateway-audit-logs-topic --project=hdrinnovationgatewayprod
gcloud pubsub subscriptions describe gateway-audit-logs-bq-subscription --project=hdrinnovationgatewayprod
gcloud monitoring time-series list --project=hdrinnovationgatewayprod \
  --filter='metric.type="pubsub.googleapis.com/subscription/dead_letter_message_count"'


# (b) Cloud Logging — the app's own request logs. CloudLoggerService just writes
#     to Laravel's \Log (stdout -> Cloud Logging), and LoggingContext attaches
#     url, http_method, controller and x-request-session-id. This is the only
#     source that shows the actual PUT /users/2 requests and the OIDC callback.
#     Default retention is 30 days, so this covers the recent window only.

gcloud logging read \
  'resource.type="k8s_container" AND resource.labels.project_id="hdrinnovationgatewayprod"
   AND (jsonPayload.url=~"/api/v1/users/2" OR jsonPayload.url=~"auth/openathens")' \
  --project=hdrinnovationgatewayprod --freshness=30d --limit=500 --format=json

# Everything sharing a session id with a misrouted login, once you have one:
gcloud logging read \
  'jsonPayload."x-request-session-id"="<session-id>"' \
  --project=hdrinnovationgatewayprod --freshness=30d --format=json
*/
