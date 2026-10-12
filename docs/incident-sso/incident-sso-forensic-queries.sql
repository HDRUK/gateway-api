-- Forensic queries for docs/incident-sso-account-confusion.md
-- Target: MySQL (gateway-api production database), read-only.
-- Replace @suspect with the account under investigation (prod report: 2).

SET @suspect = 2;
SET @bug_introduced = '2024-10-01';   -- commit 7c5d8e70

-- ---------------------------------------------------------------------------
-- 0. The audit trail is NOT in this database
-- ---------------------------------------------------------------------------
-- `audit_logs` is empty and will stay empty: App\Auditor\Auditor publishes every
-- audit event to Pub/Sub (gateway-audit-logs-topic), and the only writer of this
-- table is AuditLogController@store via POST /api/v1/audit_logs, which nothing
-- calls. The trail lives in BigQuery:
--
--   hdrinnovationgatewayprod.hdr_gateway_audit_logs_prod.hdr_gateway_audit_logs_prod
--
-- See docs/incident-sso-forensic-queries-bigquery.sql for the login, overwrite
-- and hijacked-session queries. This file now covers only what MySQL can answer:
-- the account state, the candidate set, and identity attribution.
--
-- `activity_logs` is a Gateway 1 legacy table (it still carries user_id_mongo /
-- version_id_mongo). Nothing in the login or profile-update path writes it —
-- ActivityLog is only touched by the ActivityLogController CRUD endpoints and the
-- seeders, and the frontend never calls them. Confirm and move on:

SELECT COUNT(*) AS rows_total, MIN(created_at) AS earliest, MAX(created_at) AS latest
FROM   activity_logs;

-- ---------------------------------------------------------------------------
-- 1. The candidate set: every account the null-providerid lookup could return
-- ---------------------------------------------------------------------------
SELECT id, name, firstname, lastname, email, secondary_email, provider, providerid,
       organisation, domain, is_admin, created_at, updated_at, deleted_at
FROM   users
WHERE  providerid IS NULL OR providerid = ''
ORDER  BY id;

-- Confirms the doc's hypothesis for why prod resolves to id=2 and dev to id=1.
SELECT id, email, provider, providerid, deleted_at FROM users WHERE id IN (1, 2);

-- Current (post-corruption) state of the suspect account. `updated_at` is the
-- timestamp of the most recent overwrite; `email` is whoever was misrouted last.
SELECT * FROM users WHERE id = @suspect\G

-- ---------------------------------------------------------------------------
-- 2. Who were they? Institute attribution
-- ---------------------------------------------------------------------------
-- The audit trail never stores the IdP claims or the written name/email, so the
-- only in-database identity evidence is: (a) the surviving values on the
-- account, (b) rows the hijacked sessions created, (c) the IdP scope domains of
-- accounts that did log in correctly.

-- (a) Last person misrouted into the account.
SELECT id, name, firstname, lastname, email, secondary_email, organisation, domain,
       SUBSTRING_INDEX(email, '@', -1) AS email_domain, updated_at
FROM   users
WHERE  id = @suspect;

-- Secondary-email verification attempts on the account leave a dated trail of
-- addresses that profile syncs tried to attach.
SELECT * FROM email_verifications WHERE user_id = @suspect ORDER BY created_at;

-- (b) Anything the hijacked sessions authored under the account id.
SELECT 'cohort_requests' AS src, id, user_id, created_at, updated_at
FROM   cohort_requests WHERE user_id = @suspect
UNION ALL
SELECT 'cohort_request_logs', id, user_id, created_at, updated_at
FROM   cohort_request_logs WHERE user_id = @suspect
ORDER  BY created_at;

-- (c) The OpenAthens population that resolved correctly: email is
--     eduPersonTargetedID . eduPersonScopedAffiliation, so the part after the
--     last '@' is the institutional scope -> the institute.
SELECT SUBSTRING_INDEX(email, '@', -1) AS idp_scope,
       COUNT(*)                        AS accounts,
       MIN(created_at)                 AS first_account,
       MAX(updated_at)                 AS last_activity
FROM   users
WHERE  provider = 'open-athens'
GROUP  BY idp_scope
ORDER  BY accounts DESC;

-- Accounts whose email has no local part (affiliation string only) — evidence of
-- a malformed claim having been written straight through.
SELECT id, email, provider, providerid, created_at, updated_at
FROM   users
WHERE  provider = 'open-athens'
  AND  (email LIKE '@%' OR email NOT LIKE '%@%' OR providerid = '' OR providerid IS NULL)
ORDER  BY created_at;

-- ---------------------------------------------------------------------------
-- 3. Scale of the silent failure, measured without the audit trail
-- ---------------------------------------------------------------------------
-- A correct OpenAthens login creates or updates an open-athens user row; a
-- misrouted one touches the suspect account instead. Compare this series with
-- the per-day OpenAthens login counts from the BigQuery pack: the gap is the
-- affected population.
SELECT DATE(created_at) AS day, COUNT(*) AS oa_accounts_created
FROM   users
WHERE  provider = 'open-athens'
  AND  created_at >= @bug_introduced
GROUP  BY day
ORDER  BY day;

SELECT DATE(updated_at) AS day, COUNT(*) AS oa_accounts_touched
FROM   users
WHERE  provider = 'open-athens'
  AND  updated_at >= @bug_introduced
GROUP  BY day
ORDER  BY day;
