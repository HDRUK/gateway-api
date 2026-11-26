# OpenAthens SSO email

How the API sets `users.email` for users who sign in with OpenAthens (`provider = 'open-athens'`). Introduced in GAT-9881.

## Background

OpenAthens (via Keystone) releases these claims by default: `sub`, `eduPersonScopedAffiliation`, and one or both of `eduPersonTargetedID` / `pairwiseID`. It does **not** send `email`/`mail` unless the Keystone application asks for it **and** the user's institution is willing to release it.

`users.email` is `NOT NULL` and unique, so every user needs *some* value.

### What used to happen

Logins before GAT-9881 stored `<identifier><affiliation>`, glued together with nothing in between:

| Identifier sent                | Stored "email"                                    | Valid? |
|--------------------------------|---------------------------------------------------|--------|
| `abc123` (eduPersonTargetedID) | `abc123member@example.ac.uk`                      | Yes (but junk) |
| `xyz=@example.ac.uk` (scoped pairwiseID) | `xyz=@example.ac.ukmember@example.ac.uk` | **No** - two `@` |

The web client sends the whole user back when the profile is saved, so the invalid ones failed `UpdateUser` validation with "The email must be a valid email address." As institutions move to `pairwiseID`, more users will hit this.

## Current behaviour

`SocialLoginController::openathensResponse()` resolves the email in this order:

1. **Released email.** The `email` claim (OIDC) or `mail` (SAML name), as a string or a single-item list, used only if it is a valid address.
2. **Placeholder.** Otherwise `<sha256(identifier)[0..40]>.<affiliation-local>@<affiliation-scope>.invalid`, e.g. `3f9a….member@example.ac.uk.invalid`. If there is no usable affiliation, `<hash>@openathens.invalid`.

Why the placeholder looks like this:
- It passes Laravel's `email` rule, so profile saves don't fail.
- `.invalid` is a reserved TLD (RFC 2606), so mail can never be delivered to it.
- It doesn't expose the persistent identifier.
- It keeps the `member@`/`staff@`/… pattern that `gateway-web-2` `ProfileForm` `isScrambled()` looks for, so the web client still treats it as a non-real address and points notifications at the secondary email. No frontend change was needed.

### Existing users (on login)

`refreshOpenAthensEmail()` runs whenever an existing OpenAthens user logs in:

- **A real email was released:** store it, unless another account (including soft-deleted ones) already has it. In that case, keep the current value, log it to the audit log, and **still let the user log in**.
- **No email was released:** replace the stored value with the placeholder only if it is a legacy concatenated address (exact match on the old formula, or invalid). A previously stored real email is never downgraded to a placeholder.

Matching an OpenAthens login to a user is still done on `providerid` only; email is never used for matching (see GAT-9679).

### Profile updates

`PUT /api/v1/users/{id}`: for OpenAthens users, `email` is accepted but ignored. The SSO email is set by login only. Validation of `email` is relaxed for these users so that a legacy value echoed back by the client doesn't block the save.

## Getting real emails (not done in code yet)

1. Request `mail` (and ideally `givenName`/`sn`) for the application in OpenAthens Keystone.
2. Institutions still decide whether to release it. Look into joining the REFEDS Research & Scholarship entity category through the UK Access Management Federation, which many institutions use to release `mail` automatically. Confirm with OpenAthens support that this works with Keystone.
3. The login request still uses `scope=openid`. Confirm with OpenAthens whether `email`/`profile` scopes are needed or accepted before changing it.

Collecting real email addresses means processing more personal data. Check with the data protection lead that the privacy notice covers it.

## Not yet handled

- Users who never log in again keep their legacy address until a one-off backfill is run.
