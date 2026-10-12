-- One table: every bad login session on user_id 2, and what that person did.
-- Companion to docs/incident-sso-account-confusion.md. GoogleSQL (BigQuery).
-- Read-only, standalone, creates nothing — paste it straight into the console.
--
-- A session is opened by any LOGIN that resolved to user_id 2 and runs until
-- the poisoned JWT expires (86400s, JWT_EXPIRATION in config/jwt.php:6) or the
-- next such login supersedes it. Audit rows with user_id = 2 inside that window
-- are what the misrouted person did; rows outside every window are the service
-- account's own traffic.
--
-- Do NOT filter these logins on description LIKE '%open-athens%'. The audit
-- description interpolates $user->provider — the provider column of the account
-- the login RESOLVED TO, not the provider used to log in — and
-- SocialLoginController::updateUser() deliberately leaves `provider` untouched
-- on the open-athens path. A misrouted login therefore records whatever
-- user_id 2's provider column already said. Filtering on 'open-athens' matches
-- only logins that resolved correctly, which is the opposite of what we want.
-- login_description is returned as a column instead, so you can see what each
-- session actually recorded and judge it.
--
-- Change the literal 2 to investigate a different account, and 86400 if
-- JWT_EXPIRATION has moved.
--
-- If this returns no rows at all, the account has no LOGIN rows in the window.
-- Check why before concluding anything, with:
--
--   SELECT description, COUNT(*) AS n, MIN(created_at) AS first_seen,
--          MAX(created_at) AS last_seen
--   FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
--   WHERE  action_type = 'LOGIN' AND target_user_id = 2
--   GROUP  BY description ORDER BY n DESC;
--
--   SELECT target_user_id, COUNT(*) AS n
--   FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
--   WHERE  action_type = 'LOGIN'
--   GROUP  BY target_user_id ORDER BY n DESC LIMIT 50;
--
-- LOGIN rows only exist from 2024-03-11 (commit c4045732, "audit part 2"), which
-- still predates the bug (2024-10-01).
--
-- There is no reliable provider/institute per session, and this file does not
-- guess one. Checked and ruled out:
--
--   * target_user_id / login_description — both describe account 2, the wrong
--     account, not the person who logged in. See the caution above.
--   * eduPersonTargetedID / eduPersonScopedAffiliation (the claims that would
--     name the institute) are read in openathensResponse() and used to build
--     providerid/email, but never written to the audit trail. They do not
--     survive past the request that logged them in.
--   * SSO duplicate-email EXCEPTION rows (see
--     docs/incident-sso-forensic-queries-bigquery.sql section 7) look tempting
--     to join on timestamp, but are a DIFFERENT code branch: an exception only
--     fires when the providerid lookup found NO row and saveUser()'s insert
--     then collided. The account-2 misroute requires the lookup to find A row
--     (the account itself) and never reaches saveUser(). The two cannot be the
--     same request, so joining them by time window would fabricate a link the
--     code rules out — not done here.
--
-- The only sources that can name these people are outside this table: HubSpot
-- contact history for account 2 (updateOrCreateContact() is called on every
-- overwrite), Cloud Logging for the raw OIDC callback (30-day retention), and
-- the reporter's own screenshots. See section 7 of the forensic-queries file
-- for the timestamps to search against.

WITH sessions AS (
  SELECT ROW_NUMBER() OVER (ORDER BY login_at) AS session_no,
         login_at,
         login_description,
         LEAST(
           TIMESTAMP_ADD(login_at, INTERVAL 86400 SECOND),
           COALESCE(LEAD(login_at) OVER (ORDER BY login_at), TIMESTAMP '9999-12-31 00:00:00+00')
         ) AS session_end
  FROM (
    SELECT created_at  AS login_at,
           description AS login_description
    FROM   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod`
    WHERE  action_type = 'LOGIN'
      AND  target_user_id = 2
  )
),

events AS (
  SELECT s.session_no,
         a.created_at, a.action_type, a.action_name,
         a.team_id, a.target_user_id, a.target_team_id
  FROM   sessions s
  JOIN   `hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod` a
         ON a.user_id    = 2
        AND a.created_at >= s.login_at
        AND a.created_at <  s.session_end
),

per_action AS (
  SELECT session_no, action_name, COUNT(*) AS n
  FROM   events
  GROUP  BY session_no, action_name
),

action_list AS (
  SELECT session_no,
         STRING_AGG(FORMAT('%s x%d', action_name, n), ' | ' ORDER BY n DESC LIMIT 15) AS top_actions
  FROM   per_action
  GROUP  BY session_no
),

per_session AS (
  SELECT session_no,
         MAX(created_at)                                       AS last_activity,
         COUNT(*)                                              AS events,
         COUNT(DISTINCT action_name)                           AS distinct_actions,
         COUNTIF(action_type = 'GET')                          AS gets,
         COUNTIF(action_type = 'CREATE')                       AS creates,
         COUNTIF(action_type = 'UPDATE')                       AS updates,
         COUNTIF(action_type = 'DELETE')                       AS deletes,
         COUNTIF(action_type = 'EXCEPTION')                    AS exceptions,
         COUNTIF(action_name LIKE 'usercontroller@update%')    AS profile_overwrites,
         COUNTIF(action_type = 'GET'
                 AND (action_name LIKE '%cohort%'
                   OR action_name LIKE '%dataaccess%'
                   OR action_name LIKE '%user%'))              AS pii_reads,
         COUNTIF(action_name LIKE '%role%'
              OR action_name LIKE '%permission%'
              OR action_name LIKE '%team%')                    AS role_team_actions,
         COUNT(DISTINCT IF(team_id        > 0, team_id,        NULL)) AS teams_touched,
         COUNT(DISTINCT IF(target_user_id > 0, target_user_id, NULL)) AS users_targeted
  FROM   events
  GROUP  BY session_no
)

SELECT s.session_no,
       s.login_at,
       s.session_end,
       p.last_activity,
       TIMESTAMP_DIFF(p.last_activity, s.login_at, MINUTE) AS active_minutes,
       IFNULL(p.events, 0)             AS events,
       IFNULL(p.distinct_actions, 0)   AS distinct_actions,
       IFNULL(p.gets, 0)               AS gets,
       IFNULL(p.creates, 0)            AS creates,
       IFNULL(p.updates, 0)            AS updates,
       IFNULL(p.deletes, 0)            AS deletes,
       IFNULL(p.exceptions, 0)         AS exceptions,
       IFNULL(p.profile_overwrites, 0) AS profile_overwrites,
       IFNULL(p.pii_reads, 0)          AS pii_reads,
       IFNULL(p.role_team_actions, 0)  AS role_team_actions,
       IFNULL(p.teams_touched, 0)      AS teams_touched,
       IFNULL(p.users_targeted, 0)     AS users_targeted,
       a.top_actions,
       s.login_description
FROM   sessions s
LEFT   JOIN per_session p USING (session_no)
LEFT   JOIN action_list a USING (session_no)
ORDER  BY s.session_no;
