# Incident: SSO login resolves to wrong (privileged) account

## Summary

A user logging in via institutional SSO (OpenAthens-style federated OIDC) can
be authenticated as an **unrelated, unintended account** — in the reported
case, the Gateway superadmin service account (`id = 2`,
`services@hdruk.ac.uk`). This grants the attacker/affected user that
account's full admin access: visibility of all teams and Cohort Discovery
access requests (including applicant PII across ~600+ requests) and apparent
write access to teams, access requests, and admin role grants.

`id = 2` is **not** a hardcoded fallback anywhere in the code — there is no
`User::find(2)` or config pointing at a specific account. The lookup query
(`User::where('providerid', null)->first()`) has no `ORDER BY`, so it returns
whichever row the database happens to return first for `providerid IS NULL`.
On this dev database that row is `id = 1` (a different service account,
`developers@hdruk.ac.uk`); in production it has apparently resolved to
`id = 2`, most likely because `id = 1` doesn't exist or is excluded in that
environment (e.g. soft-deleted) — **not confirmed**, needs checking directly
against production. 23 accounts on the dev DB (likely a different count in
prod) currently have `providerid IS NULL` and are therefore candidates this
query could return; the set is not limited to service accounts, and which
one comes back is an implementation detail of the database's scan order, not
a guarantee — it could change if the table's data changes.

No special steps are required to trigger it — a normal SSO login is
sufficient. It has been confirmed with two completely independent IdPs
(DKFZ, a university), which both exhibit the same failure.

## Reported by

External report received 2026-10-08 from Martin Lablans, responsibly
disclosed (not made public, no data retained beyond screenshots, offered to
help reproduce).

## Root cause

File: `app/Http/Controllers/Api/V1/SocialLoginController.php`

The `openathens` login path (used for federated institutional IdP logins,
handled directly via `Jumbojett\OpenIDConnectClient` rather than Socialite)
builds a `providerid` from the `eduPersonTargetedID` claim:

```php
// app/Http/Controllers/Api/V1/SocialLoginController.php:553
private function openathensResponse(array $data, string $provider): array
{
    $targetedId = is_array($data['eduPersonTargetedID']) ? $data['eduPersonTargetedID'][0] : $data['eduPersonTargetedID'];
    $affiliation = is_array($data['eduPersonScopedAffiliation']) ? $data['eduPersonScopedAffiliation'][0] : $data['eduPersonScopedAffiliation'];
    return [
        'providerid' => $targetedId,
        ...
        'email' => $targetedId . $affiliation,
        ...
    ];
}
```

This is then used to look up the local user (`:408`, and `:376` for the
`registry` provider):

```php
$user = User::where('providerid', $socialUserDetails['providerid'])->first();
```

**Failure chain:**

1. Different IdPs return `eduPersonTargetedID` in different shapes. When the
   claim is missing or not a plain 0-indexed array, `$data['eduPersonTargetedID'][0]`
   silently evaluates to `null` (PHP emits an undefined-index warning, not an
   exception).
2. `$targetedId` is `null`, so `providerid` in the lookup is `null`.
3. Laravel's query builder treats `where('providerid', null)` as
   `whereNull('providerid')` — this is standard Eloquent behaviour, not a
   bug in the framework itself.
4. `providerid` is a nullable column. Every user who has never done an
   OpenAthens-style login — including seeded/service accounts — has
   `providerid IS NULL`. `->first()` deterministically returns whichever
   row comes first for that condition (observed: the superadmin service
   account, id 2).
5. That account is then passed to `updateUser()` and a fresh JWT is minted
   for **its** id (`createJwt($user)` → `JwtController::generateToken($user->id)`).
   The real visitor is silently logged in as that account, regardless of who
   they are or which IdP they used.

This explains the report exactly: two unrelated IdPs, no special steps,
consistently resolving to the same wrong, highly-privileged account.

## Introduced by

Commit [`7c5d8e70`](https://github.com/HDRUK/gateway-api/commit/7c5d8e709aeb25630b630166557fa7f137fbeaf9)
— "handle arrays in OA response", Branwen Snelling, 2024-10-01.

This added the `is_array(...) ? [...][0] : ...` handling without validating
that the result was a non-empty string before using it as an identity key
for a DB lookup.

## Blast radius

Not limited to DKFZ/Heidelberg. Any IdP whose `eduPersonTargetedID` /
`eduPersonScopedAffiliation` claims don't come back as a plain 0-indexed
array will hit this path. This may be the common case for IdPs outside
whatever shape the original UK federation testing used — the bug may have
been live and silently misauthenticating institutional logins since
2024-10-01.

## Secondary effect: why the account's name and email keep changing

The wrong-account login (above) explains *who* gets logged in; this explains
why the misrouted account's displayed name (and email) visibly changes each
time, rather than staying fixed at one wrong identity.

**Write path:** `app/Http/Controllers/Api/V1/UserController.php:449`
`update()`, routed from `PUT`/`PATCH /users/{id}` (config/routes.php
~`:1361`/`:1375`):

```php
// :458-461
'name' => $input['firstname'] . " " . $input['lastname'],
'firstname' => $input['firstname'],
'lastname' => $input['lastname'],
'email' => $input['email'],
...
// :532
$user->update($array);
```

**Guard:** `check.access.userId` middleware →
`App\Http\Middleware\CheckUserIdMatches` (`bootstrap/app.php:96`):

```php
if ($user['id'] == $routeId || $jwtUserIsAdmin) {
    return $next($request);
}
```

This middleware is not itself broken — it correctly allows a user to edit
their own profile (or an admin to edit anyone's), and blocks editing someone
else's. The problem is upstream: it is trusting a JWT that the SSO bug
already minted for the wrong account.

**The chain:**

1. A real person logs in via OpenAthens-style SSO. The `providerid` bug
   (see Root cause) silently resolves them to an unrelated existing
   account — in the observed case, the superadmin service account.
2. The frontend, as a normal post-login step, syncs the user's profile by
   calling `PUT /users/{id}` with the real person's `firstname`/`lastname`/
   `email` taken from the IdP's claims. `{id}` comes from the just-issued
   (poisoned) JWT.
3. `CheckUserIdMatches` sees `jwt_user.id == routeId` and allows the
   request — as far as it can tell, the user is editing their own profile.
4. `UserController::update()` overwrites `name`, `firstname`, `lastname`,
   and `email` on the wrong account with the real person's details.
5. The next person misrouted into the same account (by the same bug)
   repeats step 2–4 with *their* details, overwriting it again — which is
   why the name (and email) has been observed to keep changing.

This is not a second independent vulnerability requiring its own fix — it is
the first bug's blast radius. There is no separate flaw in
`UserController::update()` or `CheckUserIdMatches` to patch; both behave
correctly given a trustworthy JWT. Fixing the root cause (below) stops JWTs
from ever being minted for the wrong account, which stops this corruption
as a direct consequence.

One consequence worth treating as urgent in its own right: **the affected
account's registered `email` has likely already been overwritten** by
whoever most recently triggered this path. If that account supports any
email-based flow (password reset, notifications, re-authentication), that
is a live account-takeover exposure independent of the SSO bug, and should
be checked/restored regardless of how quickly the root cause is patched.

## Code fix report

### Affected locations

| Location | Issue |
|---|---|
| `SocialLoginController::openathensResponse()` (~`:551`) | Can return `providerid => null` when `eduPersonTargetedID` is missing or not a plain 0-indexed array. Root cause. |
| `SocialLoginController::callback()` (`:408`) | `User::where('providerid', $socialUserDetails['providerid'])->first()` — consumes the bad value, becomes `whereNull`. |
| `SocialLoginController::dtaCallback()` (`:270`) | Same call, duplicated for the DTA-proxied callback path. Same exposure. |
| `SocialLoginController::registryResponse()` (`:631`) | Defaults `providerid` to `''` rather than `null`, so it does not trigger the `whereNull` behaviour — not exploitable the same way, but an empty-string `providerid` is still not a valid identity and should be rejected too (defence in depth). |
| `SocialLoginController::callback()` (`:376`) | `User::where('providerid', $socialUserDetails['providerid'])->first()` for the `registry` provider — same defensive gap as above. |

### Fix 1 — `openathensResponse()`: never emit a null/empty `providerid`

```php
private function openathensResponse(array $data, string $provider): array
{
    $targetedId = $data['eduPersonTargetedID'] ?? null;
    $targetedId = is_array($targetedId) ? ($targetedId[0] ?? null) : $targetedId;

    $affiliation = $data['eduPersonScopedAffiliation'] ?? null;
    $affiliation = is_array($affiliation) ? ($affiliation[0] ?? null) : $affiliation;

    if (!is_string($targetedId) || trim($targetedId) === '') {
        throw new Exception('Unable to resolve SSO identity: missing or malformed eduPersonTargetedID claim');
    }

    return [
        'providerid' => $targetedId,
        'name' => '',
        'firstname' => '',
        'lastname' => '',
        'email' => $targetedId . $affiliation,
        'provider' => $provider,
        'password' => Hash::make(json_encode($data)),
    ];
}
```

This throws before a `providerid` ever reaches a query, for both callers
(`callback()` and `dtaCallback()`) in one place.

### Fix 2 — belt-and-braces guard at both query call sites (`:376`/`:408`/`:270`)

Add an explicit non-null, non-empty check immediately before each
`User::where('providerid', ...)` call, so this class of bug cannot resurface
through a different response-builder in future without an exception being
thrown:

```php
if (!is_string($socialUserDetails['providerid'] ?? null) || trim($socialUserDetails['providerid']) === '') {
    throw new Exception('Unable to resolve SSO identity: empty provider id');
}
$user = User::where('providerid', $socialUserDetails['providerid'])->first();
```

This is deliberately redundant with Fix 1 — it protects the `registry`
branch too, and protects against any future response-builder making the
same mistake.

### Why not just add `->whereNotNull('providerid')` globally?

That would mask the symptom (stop the wrong-account match) but let the
underlying SSO login silently fail into `saveUser()`-with-garbage-data or a
confusing error instead of a clear, loud failure. Throwing as early as
possible (at claim-parsing time) gives an honest error back to the user and
a clean audit trail, rather than quietly creating/matching a bad account.

### Test plan

- Unit test `openathensResponse()` with: `eduPersonTargetedID` absent;
  present as `null`; present as an empty array; present as an associative
  array without a `0` key; present as a normal 0-indexed array (happy path).
  Assert the first four throw and the last returns the expected
  `providerid`.
- Feature test hitting `callback()`/`dtaCallback()` with a mocked OIDC
  response missing `eduPersonTargetedID` — assert a 4xx/error response and
  **no JWT issued**, and that no existing user record is matched or
  mutated.
- Regression test for the happy path (well-formed claim) to confirm
  legitimate SSO logins still match/create the correct user by
  `providerid`.
- Confirm via Behat/manual test that a legitimate OpenAthens login for a
  real, previously-registered `providerid` still succeeds post-fix.

### Fix 3 (hardening, optional) — guard against profile-sync overwriting high-privilege accounts

Fixes 1–2 stop the wrong JWT from ever being issued, which is what actually
stops the name/email corruption. As defence in depth, `UserController::update()`
could additionally refuse to let a self-service profile update (as opposed
to an admin-initiated one) change `email`/`name` on an account with
`is_admin = 1` or `provider = 'service'` without extra verification. Not
required to close this incident, but would have limited the damage even
with the root cause still present.

### Rollout

1. Apply Fix 1 + Fix 2 together (same PR).
2. Deploy as a hotfix ahead of the normal release cadence, given this is an
   active privilege-escalation path.
3. After deploy, audit `users` table for any account whose `providerid`
   was set to a literal empty string or suspicious concatenation (e.g.
   `email` = just an affiliation string with no local part) as evidence of
   prior exploitation of this path, in addition to the login/write-action
   audit already listed under Containment above.

## Suggested containment (pending fix deployment)

- Take the `openathens` SSO login route offline, or add the guard above as
  an emergency hotfix.
- Invalidate active sessions/JWTs for the superadmin service account
  (`id = 2`) and rotate any associated credentials.
- Audit login and write-action logs for account `id = 2` (and any other
  accounts with `providerid IS NULL`) to determine whether this has already
  been exploited beyond today's report.
- Pull all `UserController@update` audit log rows (`action_name:
  'UserController@update'`) targeting `id = 2` (and other `providerid IS NULL`
  accounts) to reconstruct the history of name/email overwrites and identify
  which real people were misrouted into them over time.
- Restore the correct `name`/`email`/`firstname`/`lastname` on the affected
  account(s) once the current (likely incorrect) values are confirmed, and
  verify no email-based flow (password reset, notifications) on that account
  is currently pointed at an external person's address.
- Acknowledge receipt to the reporter and provide a named security contact.
