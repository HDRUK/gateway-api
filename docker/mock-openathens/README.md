# Mock OpenAthens IdP

A deliberately minimal OpenID Connect provider that stands in for OpenAthens so
Gateway's institutional login can be driven end to end on a laptop — including
the failure cases, which are the point.

For *why* this exists, how to configure Gateway against it, and the catalogue of
things to break, see [`docs/local-openathens-testing.md`](../../docs/local-openathens-testing.md).
This README is about **the code**: how to read it, and how to extend it.

```
server.js        the whole IdP, ~290 lines, Node standard library only
profiles.json    the identities it can return — the file you will edit most
Dockerfile       node:20-alpine, if you'd rather not run it on the host
```

Run it with `node server.js`. No `npm install`, no lockfile, no dependencies.

## There are no passwords

The picker has no login form, and `profiles.json` has no credentials. That is
deliberate, and worth understanding before you go looking for the password.

In a real OpenAthens login the user authenticates at **their institution**, not
at Gateway. Gateway receives an authorisation code, exchanges it for a token,
and asks for claims — credentials never reach the relying party. So there is no
password anywhere in the code path this harness exercises, and nothing for the
mock to check.

A profile is therefore not an account. It is the claims payload an IdP returns
*after* a successful authentication. Choosing one from the dropdown is the
equivalent of "this person has just proved they are
`OA-TARGETED-ID-0001@example.ac.uk`" — the proof itself is what the mock skips.

The Gateway account that results has no usable password either:
`openathensResponse()` sets it to `Hash::make(json_encode($data))`, a hash of
the claims blob whose plaintext nobody knows. OpenAthens users cannot fall back
to email-and-password login.

The security consequence is the reason for the warning further down: anyone who
can reach this port gets a validly-signed token for any identity in the file,
with no credential at all.

## What it has to satisfy

The shape of this code is dictated entirely by what Gateway's OIDC client
(`jumbojett/openid-connect-php`, HDR fork) demands. Read this table first and
the rest of the code stops looking arbitrary:

| Constraint | Consequence in `server.js` |
|---|---|
| Endpoints hardcoded as `{issuer}/oidc/{auth,token,jwks,userinfo}` — no discovery | The router matches those four literal paths. No `.well-known` is served. |
| `iss` must equal `OPENATHENS_ISSUER_URL` byte for byte | `issuer` is one constant, used for both the ID token `iss` and the `URL` parse base |
| ID token must be RS256, verifiable against the JWKS | `signingKey` is generated at boot; `publishedJwks()` exports its public half |
| Gateway never sends a `nonce`, and compares any `nonce` claim against nothing | `signIdToken()` omits `nonce` entirely unless the `nonce` fault is set |
| Token auth is `client_secret_basic`, and PHP URL-encodes each half before base64 | `clientCredentials()` base64-decodes, splits on the first `:`, then `phpUrlDecode`s both halves |
| Identity is read from userinfo, not the ID token (`scope=openid` only) | The ID token carries only the mandatory claims; all identity lives in `profiles.json` |
| Userinfo is a `GET` with `?schema=openid` and must be `application/json` | `handleUserinfo` is registered for GET and answers with `sendJson` |
| No PKCE | No `code_challenge` handling anywhere |

## Reading order

Skip to the bottom first. The `http.createServer` call at the end is the table
of contents — five routes, each delegating to one handler. Then read the
handlers in request order:

1. **`handleAuthorize`** — validates `client_id`, `response_type` and
   `redirect_uri`; with no `profile` yet it renders the picker, and once a
   profile is chosen it mints a code and redirects back.
2. **`handleToken`** — authenticates the client, redeems the code, enforces the
   `redirect_uri` match, and returns the ID token.
3. **`handleUserinfo`** — looks up the access token and returns the profile's
   claims verbatim.

Everything above those three is support: key generation, JWT signing, and small
HTML and JSON helpers.

## The two pieces of state

Both are plain in-memory `Map`s, both lost on restart.

```
issuedCodes   code        → { profileName, redirectUri, faults }
issuedTokens  accessToken → { profileName, redirectUri, faults }
```

The same record object is handed from one to the other at token exchange, which
is how a fault chosen on the login page survives all the way to userinfo. Codes
are deleted on redemption, so replay produces `invalid_grant` — matching a real
provider.

## Following one login through

```
GET  /oidc/auth?client_id&redirect_uri&response_type=code&scope=openid&state
     → handleAuthorize: no `profile` param, so renderPicker()

GET  /oidc/auth?…&profile=valid&break=
     → handleAuthorize: issuedCodes.set(code, {...})
     → 302 {redirect_uri}?code=…&state=…      state echoed back untouched

POST /oidc/token        Authorization: Basic …
     → handleToken: clientCredentials() → code lookup → redirect_uri check
     → { access_token, id_token, token_type, expires_in }

GET  /oidc/jwks
     → publishedJwks()                        Gateway verifies the signature

GET  /oidc/userinfo?schema=openid    Authorization: Bearer …
     → handleUserinfo: { sub, ...profile.claims }
```

`state` is never inspected, only echoed. That is correct — `state` belongs to
the client, and what is being exercised is Gateway's own `hash_equals` check
against its session.

## Fault injection

Faults are chosen in the **Inject faults** box on the picker page, as a
comma-separated list. They cannot be passed on the Gateway login URL: Gateway
builds the authorize URL from a fixed five-parameter list and drops everything
else.

`faultsFrom()` parses the string into a `Set` stored alongside the code, so each
fault is a one-line `faults.has('…')` branch at the place it applies:

| Fault | Where it lives | What it does |
|---|---|---|
| `iss` | `signIdToken` | Issues the token as `https://wrong-issuer.example` |
| `aud` | `signIdToken` | Addresses the token to a different client |
| `exp` | `signIdToken` | Backdates expiry by an hour |
| `nbf` | `signIdToken` | Sets "not before" an hour into the future |
| `nonce` | `signIdToken` | Adds a `nonce` claim Gateway never asked for |
| `sig` | `signIdToken` | Corrupts the last four signature characters |
| `kid` | `signIdToken` | Signs with `unpublishedKey`, absent from the JWKS |
| `userinfo` | `handleUserinfo` | Returns HTTP 500 instead of claims |
| `laxredirect` | `handleToken` | Stops enforcing the `redirect_uri` match |

`unpublishedKey` exists solely for the `kid` fault — a second real key pair that
is never published, so the signature is genuine but unverifiable.

## Adding a profile

Add an entry to `profiles.json` and restart. The key is the picker value, the
`label` is the display text, and `claims` is returned from userinfo verbatim —
including malformed values, which is the whole purpose.

```json
"my-case": {
  "label": "What this case is for",
  "claims": {
    "eduPersonTargetedID": "OA-TARGETED-ID-1234",
    "eduPersonScopedAffiliation": "member@example.ac.uk"
  }
}
```

`sub` is added automatically as `mock-{profileName}` unless the profile sets its
own.

The existing malformed entries mirror the data provider in
`tests/Feature/SocialLoginControllerTest.php`, so the two layers stay in step —
if you add a shape there, add it here too.

## Adding a fault

One row in the table above, one `faults.has('…')` branch where it applies.
Nothing registers faults centrally, by design — a fault that is never checked is
simply inert.

## What this is not

Not a conformant OpenID provider, and not a security boundary. It knows one
client, keeps everything in memory, generates fresh keys every boot, and
implements no refresh tokens, no PKCE, no discovery, no logout, no consent, no
access-token expiry enforcement, and no TLS.

**Never run it anywhere but a developer machine.** It will hand a signed token
for any identity in `profiles.json` to anyone who can reach the port.

If you need a conformant provider — to check Gateway against a real
implementation rather than this stand-in — see §8 of
[`docs/local-openathens-testing.md`](../../docs/local-openathens-testing.md),
which covers Keycloak and `navikt/mock-oauth2-server`, and the reverse proxy
they both need in order to match Gateway's hardcoded paths.

## Status

Written from the Gateway source; the full login round trip has **not yet been
run against it**. `server.js` parses and `profiles.json` is valid JSON, but
nothing beyond that has been verified. Treat first use as a shakedown, and
correct both this file and the testing doc as you go.
