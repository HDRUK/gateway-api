## Screenshots (if relevant)

N/A.

## Describe your changes

**Fix PR** — stacked on PR2 (`feat/GAT-9018-2`). PR #1703.

`summary.publisher` is now driven by the raw (post-TRASER) payload rather than synthesised from the requesting team, while its gateway identifier is normalised to a team `pid` — enabling publish-on-behalf and stopping id/pid corruption.

- **`GwdmMetadataHandler`** — add `resolveTeamFromGatewayId()` (id-or-pid, public static) and `resolvePublisherWithKeys()`; `buildPublisher()` becomes the fallback-only builder; add abstract `resolvePublisher()`.
- **`Gwdm2xHandler` / `Gwdm1xHandler`** — `resolvePublisher()` (raw + normalise `gatewayId`/`publisherId` to pid + fall back to requesting team); `prepareMetadata()` uses it.
- **Legacy V1 inline write paths** (`MetadataOnboard` trait, `IntegrationDatasetController`) apply the same normalisation via `MetadataOnboard::normalisePublisher()`.
- **`app:normalise-publisher-gateway-id`** backfill command — fixes snapshot envelopes and delta patch ops (`gatewayId` + legacy `publisherId`), leaves `original_metadata` untouched; registered in `RunPostMigrations` before the datasets reindex.
- **`FormHydrationController`** — `dataCustodian` identifier default → team pid (+ test).

## Issue ticket link

GAT-9018

## Environment / Configuration changes (if applicable)

None.

## Requires migrations being run?

No schema migration. Adds a data backfill command (`app:normalise-publisher-gateway-id`) wired into `RunPostMigrations` (runs before the datasets reindex).

## If not using the pre-push hook. Confirm tests pass:

- New: `PublisherResolutionTest`, `NormalisePublisherGatewayIdTest`.

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [x] I have created mocks for unit tests (where appropriate)
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A
- [ ] I have added Swagger annotations for new endpoints (if applicable) — no new endpoints
- [ ] I have added audit logs for new operation logic (if applicable) — N/A
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A
