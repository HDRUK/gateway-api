## Screenshots (if relevant)

N/A.

## Describe your changes

Introduces a proper version-handler abstraction for GWDM metadata instead of scattering `version_compare()` calls across `DatasetService`. All version-specific behaviour now goes through `GwdmHandlerFactory`, which resolves a GWDM version string to a handler (`Gwdm1xHandler`, `Gwdm20Handler`, `Gwdm21Handler`, falling back to `Gwdm2xHandler`). Only GWDM 2.x is enabled here — 3.0 support comes later.

- Added `GwdmVersionContext` to resolve the GWDM version per-request from an optional `x-gwdm-version` header (falls back to the configured default on write; returns null on read if not set, so the caller uses the dataset's actual latest version). Also added `OutputSchemaContext` and `PartnerContext` for the same kind of per-request resolution, plus a small `ResolveGwdmVersionContext` middleware to stash the resolved version onto the request early.
- `DatasetService` now delegates to the handler hierarchy instead of branching on version itself, and `V2/DatasetController`, `V1/FormHydrationController`, and `MetadataManagementController` were updated to match.
- Linkage data (dataset-to-dataset and publication-to-dataset relationships) now reads from the `dataset_version_has_dataset_version` / `publication_has_dataset_version` junction tables instead of the raw metadata column, via `Gwdm2xHandler::extractLinkages()` and `DatasetService::getLinkages()`. This also fixes a bug where a delta version row could wipe out linkage data because it doesn't carry full metadata.

Also fixed along the way:
- Version-row insert and its follow-up writes now happen in a single transaction, so a failure partway through can't leave an orphaned version.
- V3's `update()` now dispatches the linkage/term extraction jobs, which it wasn't doing before.
- Changing GWDM schema version between updates now forces a full metadata snapshot instead of trying to diff across incompatible schemas.

This PR should be behaviour-preserving for existing 2.x datasets — nothing here changes what a 2.x read or write actually does, just how the code gets there.

### What's not in this PR

A few things got bundled into this branch while working on it that turned out to be unrelated to the version-handler refactor, so they've been split into their own PRs:

- **`feat/GAT-9018-2-2`** — the free-text linkage fallback (raw pid/url/title/doi columns), an IndexElastic fix for delta rows reading empty metadata, and a spatial-coverage pivot pruning fix. Real bugs, just not caused by anything in this PR.
- **`feat/metadata-only-endpoint`** — turns the `?view=metadataOnly` query param on `GET /api/v2/datasets/{id}` into its own `GET /api/v2/datasets/{id}/metadata` endpoint, so the response shape doesn't silently change based on a query param.
- **`feat/dataset-version-validation-report`** — a bulk per-version TRASER validation helper (`validateAllVersions`) that isn't wired up to anything yet (no route calls it). It's not needed until GWDM 3.0 lands, so it didn't belong in this PR either.

## Issue ticket link
[GAT-9212](https://hdruk.atlassian.net/browse/GAT-9212)

## Environment / Configuration changes (if applicable)

New optional `x-gwdm-version` request header. Added `config/partners.php`.

## Requires migrations being run?

Yes — adds a `gwdm_version` column to `dataset_versions` (`database/migrations/2026_06_17_000001_add_gwdm_version_to_dataset_versions_table.php`), plus a deployment-step script to backfill it for existing rows.

## If not using the pre-push hook. Confirm tests pass:

- New: `GwdmVersionContextTest` (6), `GwdmHandlerFactoryTest` (4), `DatasetVersionSchemaChangeTest` (1), `DatasetVersionGwdmVersionColumnTest` (3).
- Regression: full dataset suite green (`DatasetTest`, `DatasetVersionTest`, `DatasetIntegrationTest` (+Deprecated), `DatasetExportTest`, `AdminDatasetControllerTest`, `DatasetsV2HelpersTest`).
- `composer run phpstan` — clean.

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [x] I have created mocks for unit tests (where appropriate) — TRASER/MMC via `MockExternalApis`
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A
- [ ] I have added Swagger annotations for new endpoints (if applicable) — no new endpoints
- [ ] I have added audit logs for new operation logic (if applicable) — N/A
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A


[GAT-9212]: https://hdruk.atlassian.net/browse/GAT-9212?atlOrigin=eyJpIjoiNWRkNTljNzYxNjVmNDY3MDlhMDU5Y2ZhYzA5YTRkZjUiLCJwIjoiZ2l0aHViLWNvbS1KU1cifQ
