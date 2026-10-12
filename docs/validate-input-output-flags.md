# `validate_input` / `validate_output` on dataset update

Context for the TRASER team on two query params gateway-api-2 now sends to
`POST /translate` on every dataset update, and why.

## Background

Gateway's dataset update endpoints (V2 `PUT /api/v2/datasets/{id}`, V3
`PUT /api/v3/datasets/{id}` and `PUT /api/v3/team/{teamId}/datasets/{id}`) call
`MetadataManagementController::translateDataModelType()`, which hits TRASER's
`/translate` endpoint. That call already accepted independent `validate_input` and
`validate_output` query params — gateway-api-2 just wasn't exposing either of them to
API callers; both were hardcoded based on draft status.

## What changed on the gateway-api-2 side

1. **`validate_input` override** (shipped earlier, commit `0c2ec58a`): callers can pass
   `?validate_input=false` to skip TRASER's input-schema validation. Added because some
   callers submit metadata TRASER's input schema rejects even though the data is
   usable downstream, and they need a way to push it through.
2. **`validate_output` override** (this change): callers can now also pass
   `?validate_output=false` to skip TRASER's output-schema validation, independently of
   `validate_input`.

Both flags default to `true` (unchanged behaviour unless a caller opts out), and both
are still forced to `false` for draft datasets regardless of what the caller passes
(drafts have never been validated either way).

## Why `validate_output` needed to be independent, not derived from `validate_input`

The motivating scenario: a caller submits metadata they believe is already in the
target GWDM schema/version, and turns off `validate_input` (maybe because TRASER's
input-schema check is rejecting it for an unrelated reason, or they're confident it's
already valid). If that belief is wrong — the metadata doesn't actually conform — we
still want TRASER's output validation to catch it. Coupling the two flags (e.g. forcing
`validate_output=false` whenever `validate_input=false`) would silently let bad metadata
through.

We confirmed this is already safe given TRASER's current implementation of
`app/routes/api/translate.ts`: the `if (validateOutput)` block runs unconditionally
after the translation step, regardless of how many translation hops actually ran — including
the passthrough case where `input_schema:input_version === output_schema:output_version`
and the translation loop runs zero iterations. So gateway-api-2 sending
`validate_input=false` and `validate_output=true` (or leaving `validate_output` at its
default) reliably still gets output-schema validation on the (unchanged) passthrough
metadata, exactly as if a real translation had happened.

**No TRASER changes were required for this** — the two flags were already structurally
independent on your side. This doc exists just to confirm that behaviour is relied upon
by gateway-api-2 now, so it should be treated as a contract: `validate_output` must keep
running regardless of whether an actual translation occurred or `validate_input` was
disabled.

## Where this is wired on the gateway-api-2 side

- `app/Services/DatasetService.php` — `update()` and `updateV2()` now take
  `bool $validateInput = true` and `bool $validateOutput = true`, passed as the 5th and
  6th args to `MMC::translateDataModelType()` (ANDed with `! $isDraft`).
- `app/Http/Controllers/Api/V2/DatasetController.php`,
  `app/Http/Controllers/Api/V3/DatasetController.php`,
  `app/Http/Controllers/Api/V3/TeamDatasetController.php` — all three `update()` actions
  read `validate_input` / `validate_output` from the request query string and forward
  them.
