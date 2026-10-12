# Testing OpenAthens login locally

How to stand up a mock OpenAthens identity provider on your own machine, drive
a real login through it, and deliberately break the configuration to learn what
each kind of failure looks like.

The harness lives in [`docker/mock-openathens/`](../docker/mock-openathens/).
It was written from the Gateway source and the full round trip has **not yet
been run** — `server.js` parses and `profiles.json` is valid, but nothing beyond
that is verified. Expect to correct details on first use, and please amend this
file when you do.

## 1. How OpenAthens actually works here

The name misleads. OpenAthens is often a SAML federation, and Gateway's other
social logins go through Laravel Socialite — **neither applies to this path**.

Gateway consumes OpenAthens as **plain OpenID Connect**, hand-rolled on
`jumbojett/openid-connect-php` (pinned to the HDR fork
`HDRUK/OpenID-Connect-PHP`, branch `dev-update-redirect-return`). All of it
lives in one file: `app/Http/Controllers/Api/V1/SocialLoginController.php`.

> `app/Http/Controllers/SSO/` is unrelated. That is Gateway acting *as* an
> OIDC provider via Passport, for RQuest and friends. Don't go looking there.

### The four endpoints

There is **no discovery**. `.well-known/openid-configuration` is never fetched.
The endpoints are hardcoded (`SocialLoginController.php:263-268`, `:420-425`):

| Endpoint | URL |
|---|---|
| Authorize | `{OPENATHENS_ISSUER_URL}/oidc/auth` |
| Token | `{OPENATHENS_ISSUER_URL}/oidc/token` |
| JWKS | `{OPENATHENS_ISSUER_URL}/oidc/jwks` |
| Userinfo | `{OPENATHENS_ISSUER_URL}/oidc/userinfo` |

This is why the mock below is purpose-built rather than off-the-shelf.
Keycloak serves `/realms/{realm}/protocol/openid-connect/*`,
`navikt/mock-oauth2-server` serves `/{issuerId}/authorize`, and so on — every
ready-made option needs a reverse proxy in front, or a change to Gateway to
make those four paths configurable.

### The flow

```
browser  GET  /api/v1/auth/openathens
         302  {issuer}/oidc/auth?client_id&redirect_uri&response_type=code&scope=openid&state
browser  (user authenticates at the IdP)
         302  {redirect_uri}?code=…&state=…
browser  GET  /api/v1/auth/openathens/callback?code&state
  api    POST {issuer}/oidc/token          (server-to-server, Basic auth)
  api    GET  {issuer}/oidc/jwks           (to verify the ID token signature)
  api    GET  {issuer}/oidc/userinfo?schema=openid   (Bearer access token)
         302  {GATEWAY_URL}/… + `token` cookie
```

### Where identity comes from

Only `scope=openid` is requested (`:169`), so the ID token carries nothing
useful. **All identity arrives from `/oidc/userinfo`.**

`openAthensIdentifier()` (`:620`) takes the first usable value of:

1. `eduPersonTargetedID`
2. `pairwiseID`

unwrapping a single-element list, and requiring a non-blank string. Anything
else throws.

`openathensResponse()` (`:595`) then builds the user record. Note the email:

```php
'email' => $providerId . $affiliation,
```

OpenAthens does not release a real email address, so Gateway **synthesises
one** by concatenating the opaque identifier with the scoped affiliation —
producing things like `OA-TARGETED-ID-0001member@example.ac.uk`. That is
intentional, and it is why OpenAthens users get `preferred_email = 'secondary'`
(`:722`, `:761`) and are pushed to `/account/profile` to supply a real address,
and why `CohortRequestController::isScrambled()` exists.

Accounts are matched on `provider = 'open-athens' AND providerid = <id>`
(`:277`, `:433`).

### Claim reference

Confirm claim names and shapes against the two pages the code itself cites:

- <https://docs.openathens.net/providers/common-openid-connect-claims>
- <https://docs.openathens.net/providers/eduperson-attributes>

### What the client actually validates

Worth knowing, because the mock has to satisfy all of it:

- **ID token signature** — RS256 against the JWKS, `kid` must resolve.
- **`iss`** must equal `OPENATHENS_ISSUER_URL` *byte for byte*.
- **`aud`** must contain the client id.
- **`exp` / `nbf`** are checked, with the library's leeway.
- **`nonce`** — Gateway builds the authorize URL by hand (`:165-172`) and never
  sends a nonce, so the client has none stored. The check is
  `!isset($claims->nonce) || $claims->nonce === $this->getNonce()`, which means
  **an IdP that volunteers a `nonce` claim breaks every login**.
- **No PKCE** is used.
- **Token endpoint auth is `client_secret_basic`** by default
  (`vendor/jumbojett/openid-connect-php/src/OpenIDConnectClient.php:868-885`).
  The header is built as
  `base64(urlencode(client_id) . ':' . urlencode(client_secret))` — note each
  half is URL-encoded first. Keep local credentials alphanumeric so an encoding
  difference can never masquerade as a wrong secret.
- **Userinfo is a `GET`** to `…/oidc/userinfo?schema=openid` with
  `Authorization: Bearer`, and must return HTTP 200. A content type of
  `application/jwt` triggers signature verification, so return plain
  `application/json`.

## 2. Do you need a real OpenAthens tenant?

For most work, no — and getting one is slow. OpenAthens service-provider
access is arranged through an OpenAthens account and its admin area, and the
client id/secret/redirect URI are registered there by an administrator. If you
need to verify against the genuine article (claim names for a specific
institution, or an IdP-initiated `target_link_uri` login), raise it with
whoever administers the HDR UK OpenAthens relationship and ask for a
non-production client registered against `http://localhost:8000/...`.

Check current sandbox availability in the provider docs before assuming one
exists — this document does not claim it does.

The mock below exists so that the ordinary case — "does our code handle this
claim shape, and what happens when the config is wrong" — needs none of that.

## 3. The mock IdP

It lives in [`docker/mock-openathens/`](../docker/mock-openathens/). Three
files, no dependencies beyond Node's standard library:

| File | What |
|---|---|
| `server.js` | The whole IdP, ~290 lines |
| `profiles.json` | The identities it can return — the file you will edit most |
| `Dockerfile` | `node:20-alpine`, if you'd rather not run it on the host |

[`docker/mock-openathens/README.md`](../docker/mock-openathens/README.md)
explains how the code is organised, how each Gateway constraint shapes it, how
fault injection threads through, and how to add a profile or a fault. Read that
before changing it; read this file to *use* it.

It serves exactly the four paths Gateway expects, and nothing else:

```
GET  /oidc/auth       picker page, then 302 back with ?code=&state=
POST /oidc/token      client_secret_basic, returns a signed RS256 id_token
GET  /oidc/jwks       the public half of the boot-time signing key
GET  /oidc/userinfo   the chosen profile's claims, verbatim
GET  /                an index listing the issuer, client and profiles
```

### Profiles

Each entry in `profiles.json` is one identity the picker can return. Alongside
the usable ones (`valid`, `valid-list`, `pairwise-only`, `second-user`) sit the
malformed shapes that mirror
`SocialLoginControllerTest::unusableOpenAthensIdentifiers` — `missing-id`,
`null-id`, `list-holding-null`, `empty-list`, `empty-string`, `blank-string`,
`two-values`, `object-id` — plus `no-affiliation`. Those are what §6b exercises.

### Optional: a compose service

Add alongside the existing `typesense` service in `docker-compose.yml`:

```yaml
  mock-openathens:
    build: ./docker/mock-openathens
    ports:
      - "8081:8081"
    environment:
      MOCK_OA_ISSUER: http://localhost:8081
      MOCK_OA_CLIENT_ID: gatewaylocal
      MOCK_OA_CLIENT_SECRET: gatewaylocalsecret
    restart: unless-stopped
```

## 4. Running it

Because you run the API directly with PHP on the host, the browser and the PHP
process share one URL namespace — `http://localhost:8081` means the same thing
to both. That is the whole reason this is easy. (If you ever move the API into
Tilt or a container, the issuer string the PHP process uses must still be
byte-identical to the one the browser was sent to, or `iss` validation fails.)

Start the mock any of these ways:

```bash
node docker/mock-openathens/server.js

docker compose up mock-openathens

docker build -t mock-openathens docker/mock-openathens
docker run --rm -p 8081:8081 mock-openathens
```

Confirm it is alive:

```bash
curl -s http://localhost:8081/oidc/jwks | head
open http://localhost:8081/
```

It logs every request and every decision to stdout, so keep that terminal
visible — it is the fastest way to tell whether Gateway reached the IdP at all,
and the only place the `redirect_uri` mismatch message appears in full:

```
2026-10-10T16:41:25.123Z  GET /oidc/auth
2026-10-10T16:41:25.124Z    issued code for profile=valid faults=[none]
2026-10-10T16:41:25.201Z  POST /oidc/token
2026-10-10T16:41:25.205Z    issued id_token for profile=valid
2026-10-10T16:41:25.210Z  GET /oidc/jwks
2026-10-10T16:41:25.260Z  GET /oidc/userinfo
2026-10-10T16:41:25.261Z    returned claims for profile=valid
```

### Find the port your API is actually on

This matters, because the redirect URLs must match it exactly.

```bash
php artisan serve --port=8000
# or, per CLAUDE.md
php artisan octane:frankenphp --host=0.0.0.0 --port=8100

lsof -nP -iTCP -sTCP:LISTEN | grep -E '8000|8100'
```

### `.env`

```dotenv
OPENATHENS_ISSUER_URL=http://localhost:8081
OPENATHENS_CLIENT_ID=gatewaylocal
OPENATHENS_CLIENT_SECRET=gatewaylocalsecret
OPENATHENS_REDIRECT_URL=http://localhost:8000/api/v1/auth/openathens/callback
OPENATHENS_REDIRECT_DTA_URL=http://localhost:8000/api/v1/auth/dta/openathens/callback

GATEWAY_URL=http://localhost:3000
DTA_URL=http://localhost:3003
SESSION_DRIVER=file
```

Swap `8000` for whichever port you found. Then:

```bash
php artisan config:clear
```

Forgetting `config:clear` is the single most common reason a change to these
values appears to have no effect.

## 5. The happy path

### Do not start from the frontend sign-in dialog

Go **directly** to the API's login URL. The sign-in dialog on
`localhost:3000` cannot reach this mock, and will leave you looking at a
spinner forever.

Its "Sign in with OpenAthens" button has no `href`
(`gateway-web-2/src/modules/ProviderLinks/ProviderLinks.tsx`) — it opens a
second step that renders `<div id="wayfinder"><Loading /></div>` and expects
OpenAthens' **hosted** institution-discovery widget to replace it, loaded from
`https://wayfinder.openathens.net/embed/v1/loader.js`
(`gateway-web-2/src/modules/ProvidersDialog/ProvidersDialog.tsx`).

Two reasons that is a dead end locally:

1. The script is only injected when `NEXT_PUBLIC_OA_APP_ID` is set. Unset — the
   usual local state — nothing ever replaces the spinner.
2. Even with an app id, Wayfinder runs on OpenAthens' domain and sends the user
   to the real OpenAthens. It cannot be pointed at `localhost:8081`.

The mock replaces institution discovery *and* authentication, so the dialog has
nothing to contribute. If you specifically need the button to work locally,
that is a separate frontend change — give the OpenAthens entry an `href` of
`apis.authOpenAthensV1Url` — and not something this harness requires.

### The round trip

1. Visit `http://localhost:8000/api/v1/auth/openathens?redirect=/search`.
2. You land on the mock's picker. Choose **`valid`**.
3. You are redirected to
   `http://localhost:8000/api/v1/auth/openathens/callback?code=…&state=…`.
4. Gateway exchanges the code, verifies the ID token, calls userinfo, and
   redirects you on with a `token` cookie set.

For a brand new user, `name` is empty, so the final redirect is
`http://localhost:3000/account/profile` (`:484-486`), **not** your `/search`.
That is correct behaviour, not a bug — OpenAthens releases no name or email, so
Gateway sends the user to fill them in.

Check what was created:

```sql
SELECT id, name, email, provider, providerid, preferred_email
FROM users WHERE provider = 'open-athens' ORDER BY id DESC LIMIT 5;
```

Expect `providerid = 'OA-TARGETED-ID-0001'`, the synthetic
`email = 'OA-TARGETED-ID-0001member@example.ac.uk'`, and
`preferred_email = 'secondary'`.

Log in again with the **same** profile: no new row, and you are logged into the
same account. Log in with `second-user`: a second, separate account. Those two
facts together are the regression test for `a63bb7b7`.

## 6. Breaking it on purpose

### First, how to see the real error

This is the most important thing in this document.

`callback()` catches every exception and redirects to
`{GATEWAY_URL}/error/500` (`:511-519`). **The browser tells you nothing.** The
message goes only to the audit log.

`dtaCallback()` rethrows (`:339-347`). So the DTA route surfaces the actual
exception message as an HTTP 500 in the browser:

```
http://localhost:8000/api/v1/auth/dta/openathens
```

Use the DTA route while diagnosing, then confirm against the Gateway route.

Otherwise:

```bash
tail -f storage/logs/laravel.log
```

```sql
SELECT created_at, action_type, description
FROM audits WHERE action_type = 'EXCEPTION'
ORDER BY id DESC LIMIT 10;
```

> Confirm the audit table name against `Auditor`'s configuration in this
> repo before relying on that query.

### Fault injection from the IdP side

Faults are chosen on the mock's own picker page, in the **Inject faults** box —
type one or more names, comma-separated, and sign in.

They cannot be passed on the Gateway login URL. `handleLogin()` builds the
authorize URL from a fixed list of five parameters (`:165-172`), so any extra
query string you add is dropped before the IdP ever sees it. The one parameter
Gateway *does* carry is `redirect`, and it keeps that in the session rather
than forwarding it.

| Fault | Effect |
|---|---|
| `iss` | ID token issued by `https://wrong-issuer.example` |
| `aud` | `aud` names a different client |
| `exp` | ID token expired an hour ago |
| `nbf` | ID token not valid for another hour |
| `nonce` | ID token carries a `nonce` Gateway never sent |
| `sig` | Signature corrupted |
| `kid` | Signed with a key absent from the JWKS |
| `userinfo` | Userinfo returns HTTP 500 |
| `laxredirect` | Token endpoint stops enforcing `redirect_uri` match |

### 6a. OIDC configuration errors

| What you change | Where it fails | What you should see |
|---|---|---|
| `OPENATHENS_ISSUER_URL` set to a wrong host | Browser never reaches an IdP | Connection error on the authorize redirect |
| `OPENATHENS_ISSUER_URL` right host, wrong path prefix | Authorize | Mock's 404 JSON, `{"error":"not_found"}` |
| fault `iss` | ID token validation | `Unable to verify JWT claims` |
| `OPENATHENS_CLIENT_ID` wrong | Authorize | Mock's "Unknown client" page — never reaches Gateway |
| fault `aud` | ID token validation | `Unable to verify JWT claims` |
| `OPENATHENS_CLIENT_SECRET` wrong | Token exchange | `client id or secret does not match this IdP` |
| `OPENATHENS_REDIRECT_URL` pointing at the wrong port | Callback | Browser lands on a dead port; Gateway never sees the code |
| fault `exp` / fault `nbf` | ID token validation | `Unable to verify JWT claims` |
| fault `nonce` | ID token validation | `Unable to verify JWT claims` |
| fault `sig` | Signature check | `Unable to verify signature` |
| fault `kid` | JWKS lookup | A signature or key-resolution failure |
| Stop the mock, then log in | JWKS fetch / token POST | A cURL/connection exception |
| fault `userinfo` | Userinfo | `The communication to retrieve user data has failed with status code 500` |

Note how many distinct causes collapse into the single message
`Unable to verify JWT claims`. `verifyJWTClaims()` is one boolean expression
over issuer, audience, subject, nonce, `exp` and `nbf`
(`OpenIDConnectClient.php:1192-1212`) — it cannot tell you which conjunct
failed. When you see it, decode the ID token and compare by eye:

```bash
# paste the id_token from the mock's token response
echo '<id_token>' | cut -d. -f2 | base64 -d 2>/dev/null | python3 -m json.tool
```

### 6b. Claim-shape errors

This is the class behind
[`docs/incident-sso-account-confusion.md`](incident-sso-account-confusion.md),
in which a malformed `eduPersonTargetedID` resolved logins to a superadmin
service account. Pick each profile from the mock's picker:

| Profile | Expected |
|---|---|
| `valid`, `valid-list` | Login succeeds |
| `pairwise-only` | Login succeeds via the `pairwiseID` fallback |
| `missing-id`, `null-id`, `list-holding-null`, `empty-list`, `empty-string`, `blank-string`, `two-values`, `object-id` | `OpenAthens response has no usable eduPersonTargetedID or pairwiseID` |
| `no-affiliation` | Verify what happens — see below |

**The check that matters.** After each rejected profile, confirm that **no
session was created and no existing account was touched**:

```sql
SELECT id, email, provider, providerid, preferred_email
FROM users WHERE providerid IS NULL ORDER BY id LIMIT 5;
```

None of those rows should have gained `preferred_email = 'secondary'`, and you
should have no `token` cookie. That is precisely the incident signature, and
exactly what `SocialLoginControllerTest` asserts — this is the browser-level
confirmation of it.

`no-affiliation` is worth its own look. `openathensResponse()` (`:598`) reads
`$data['eduPersonScopedAffiliation']` without guarding for absence. Under
PHP 8 a missing key is a warning, not an exception, so the outcome depends on
Laravel's error handling in your environment: either an `ErrorException`
(redirect to `/error/500`) or a user created with
`email = 'OA-TARGETED-ID-0003'` and no domain part. **Find out which, and
record the answer here** — if it is the latter, that is a finding worth a
ticket.

### 6c. Session and state errors

`state` is generated at login, stored in the Laravel session, and consumed with
`pull()` so it is single-use (`:162-163`, `:251-254`).

| Test | How | Expected |
|---|---|---|
| Mismatched state | Complete a login, then edit `state` in the callback URL and reload | `OpenAthens state does not match the login request` |
| Replayed state | Complete a login, then press Back and re-submit the same callback URL | Same rejection — `pull()` already consumed it |
| No login in session | Paste `…/auth/openathens/callback?code=x&state=y` into a fresh private window | Same rejection |
| Lost session cookie | Start the login on `localhost`, finish it on `127.0.0.1` | Same rejection — different cookie host, so the session is empty |

That last one is a genuinely easy mistake. `localhost` and `127.0.0.1` are
different cookie origins. Pick one and use it consistently throughout.

### 6d. A real bug this harness exposes

`dtaLogin()` sends `redirect_uri = services.openathens.dta_redirect`
(`:85` → `:167`), but `dtaCallback()` sets the OIDC client's redirect URL to
`services.openathens.redirect` (`:235`, `:270`) — the **non-DTA** value.

RFC 6749 §4.1.3 requires the token request's `redirect_uri` to match the one
used at authorize. This asymmetry was introduced by `0ff24b06` (GAT-6458),
which changed the callback from `OPENATHENS_REDIRECT_DTA_URL` to
`OPENATHENS_REDIRECT_URL` without changing the login side.

To see it, set the two to genuinely different values:

```dotenv
OPENATHENS_REDIRECT_URL=http://localhost:8000/api/v1/auth/openathens/callback
OPENATHENS_REDIRECT_DTA_URL=http://localhost:8000/api/v1/auth/dta/openathens/callback
```

then drive `http://localhost:8000/api/v1/auth/dta/openathens`. Expect the mock
to answer:

```json
{
  "error": "invalid_grant",
  "error_description": "redirect_uri mismatch: authorize used …/auth/dta/openathens/callback, token request used …/auth/openathens/callback"
}
```

Confirm with fault `laxredirect`, which disables the check — the same login
should then succeed.

The existing Pest suite cannot catch this, because `FakeOpenAthensClient`
stubs `authenticate()` out entirely and no token exchange ever happens. If it
reproduces, raise a ticket; if it does not, record why here.

## 7. The mock versus the Pest fake

Two layers, different jobs. Use the right one.

| | `tests/Fakes/FakeOpenAthensClient` | The mock IdP |
|---|---|---|
| Speed | Milliseconds | A browser round trip |
| Runs in CI | Yes | No |
| Covers claim shapes | Yes | Yes |
| Covers `state` handling | Yes | Yes |
| Covers token exchange, `redirect_uri`, signatures, `iss`/`aud`/`exp`, JWKS, userinfo transport | **No** | Yes |

`FakeOpenAthensClient` overrides `authenticate()` to `return true` and
`requestUserInfo()` to return canned JSON. Everything between the authorize
redirect and userinfo is skipped.

So: add a case to `SocialLoginControllerTest` for anything about **claim
handling or account matching** — that belongs in CI. Reach for the mock when
the question is about **configuration, transport or cryptography**, which is
the layer the fake cannot see.

```bash
php -d memory_limit=512M vendor/bin/pest tests/Feature/SocialLoginControllerTest.php
```

## 8. If you want a real IdP implementation

The mock is deliberately not a conformant OpenID provider. If you need to check
Gateway against a real one, the obstacle is always the hardcoded `/oidc/*`
paths. Two ways round it:

- **Reverse proxy.** Put nginx or Caddy in front and rewrite `/oidc/auth` →
  the provider's own path. For Keycloak that is
  `/realms/{realm}/protocol/openid-connect/auth`, with `token`, `userinfo` and
  `certs` (for `jwks`) alongside. The realm's frontend URL must be set so that
  `iss` matches `OPENATHENS_ISSUER_URL` exactly.
- **Make the paths configurable.** A small change to the
  `providerConfigParam()` block (`:263-268`, `:420-425`) would let `.env` point
  the four endpoints anywhere, removing the proxy. That is an application
  change, so it needs its own ticket and review.

If you go the Keycloak route, remember Gateway reads identity from
**userinfo**, not the ID token, so each claim needs a protocol mapper with
`userinfo.token.claim` set to `true`:

```json
{
  "protocolMapper": "oidc-usermodel-attribute-mapper",
  "config": {
    "user.attribute": "eduPersonTargetedID",
    "claim.name": "eduPersonTargetedID",
    "jsonType.label": "String",
    "userinfo.token.claim": "true"
  }
}
```

`navikt/mock-oauth2-server` (currently `ghcr.io/navikt/mock-oauth2-server:6.0.5`)
is the lighter alternative — it echoes every token claim at userinfo with no
mapper plumbing — but it serves `/{issuerId}/authorize` and friends, so it
needs the same proxy.

Neither can emit the malformed claim shapes in §6b, which is the main reason
this document ships its own mock.

## 9. A note on Octane

Production runs Octane FrankenPHP (`docker/start.sh`), and the OpenAthens path
leans on raw `session_start()` plus the `$_SESSION` and `$_REQUEST`
superglobals (`:244-256`, `:407-412`).

Under `php artisan serve` each request is a fresh process, so that code
behaves benignly. Under Octane the workers are long-lived and those globals
persist between requests. **A clean local run does not by itself clear the
superglobal usage in production.** If you are specifically investigating
cross-request contamination, reproduce under
`php artisan octane:frankenphp` with more than one worker.

## 10. Quick reference

```bash
node docker/mock-openathens/server.js            # start the IdP
php artisan serve --port=8000                    # start the API
php artisan config:clear                         # after any .env change

open http://localhost:8081/                      # the mock's profile list
open http://localhost:8000/api/v1/auth/openathens          # log in
open http://localhost:8000/api/v1/auth/dta/openathens      # log in, errors visible

tail -f storage/logs/laravel.log
```

| File | What |
|---|---|
| `docker/mock-openathens/` | The mock IdP, with its own README |
| `app/Http/Controllers/Api/V1/SocialLoginController.php` | The entire OpenAthens flow |
| `config/services.php` (`openathens`) | Config block |
| `config/routes.php` (lines 66-110) | The four routes |
| `tests/Feature/SocialLoginControllerTest.php` | Existing coverage |
| `tests/Fakes/FakeOpenAthensClient.php` | The in-process double |
| `docs/incident-sso-account-confusion.md` | Why claim validation matters |
