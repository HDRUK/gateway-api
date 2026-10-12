## Screenshots (if relevant)

N/A.

## Describe your changes

**Fix PR** — stacked on PR2 (`feat/GAT-9018-2`). Mirrors the `fix/GAT-9018-publisher-fix`/`fix/GAT-9018-linkage-title` layout: split out of PR2 (#1699) because these are independent, pre-existing bugs/features, not part of the version-handler/DatasetService rearrangement.

- **Free-text linkage fallback** — dataset/publication linkages that can't be resolved to a real row are stored with their raw external `url`/`pid`/`title`/`doi` instead of being silently dropped. New `raw_url`/`raw_pid`/`raw_title` columns on `dataset_version_has_dataset_version` and `raw_doi` on `publication_has_dataset_version`, both FKs made nullable. `Gwdm2xHandler::processDatasetLinkages()`/`processPublicationLinkages()` write the unresolved fallback; `afterRead()` and `DatasetService::getLinkages()` read it back so the frontend still gets a title/url for an unresolved link.
- **IndexElastic delta-row fix** — delta `dataset_version` rows store an empty `metadata` column by design (full GWDM lives in snapshots + patches), but ES indexing (`reindexElastic()`, `reindexElasticDataProvider()`, `indexElasticCollections()`, `checkingDataset()`) and the tool/publication extraction jobs read it directly, crashing or silently no-oping whenever a dataset's latest version happened to be a delta row. They now reconstruct the full envelope via `DatasetService::getReconstructedMetadataEnvelope()`. Also adds `GwdmMetadataHandler::supportsElasticIndexing()` so a dataset on a non-indexable GWDM version gets deindexed rather than partially/incorrectly indexed (no handler returns `false` yet — forward-looking infrastructure for GWDM 3.0), and moves Typesense field extraction out of `DatasetVersion::toSearchableArray()` into the handler hierarchy via `toSearchableFields()`.
- **Spatial-coverage pivot pruning fix** — `mapCoverage()` and `Dataset::getSpatialCoverageViaVersions()` now scope to the latest version and prune stale `dataset_version_has_spatial_coverage` rows, so editing coverage down (e.g. England → Scotland) actually removes the old entry instead of accumulating it forever.

## Issue ticket link

GAT-9018

## Environment / Configuration changes (if applicable)

None.

## Requires migrations being run?

Yes — `2026_06_18_000001_add_unresolved_linkage_columns.php`.

## If not using the pre-push hook. Confirm tests pass:

- New: `DatasetVersionLinkageTest` (2, unresolved-linkage round-trip via `getLinkages()`, `extractLinkages()` reads the reconstructed blob not a stale SQL overlay), `DatasetSpatialCoverageTest` (1, latest-version scoping), `IndexElasticDeltaRowTest` (2, `reindexElasticDataProvider`/`indexElasticCollections` against delta rows), `IndexElasticGwdmGatingTest` (2, `supportsElasticIndexing()` deindex/index gate), `ExtractMetadataDeltaRowTest` (2, tool/publication extraction against delta rows).
- Regression: full dataset suite green (`DatasetTest`, `DatasetVersionTest`, `DatasetIntegrationTest(+Deprecated)`, `DatasetExportTest`, `AdminDatasetControllerTest`, `DatasetsV2HelpersTest`), plus PR2's core suite (`GwdmVersionContextTest`, `GwdmHandlerFactoryTest`, `DatasetVersionSchemaChangeTest`).
- `composer run phpstan` — clean (0 errors).

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [x] I have created mocks for unit tests (where appropriate) — TRASER/MMC via `MockExternalApis`
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A
- [ ] I have added Swagger annotations for new endpoints (if applicable) — no new endpoints
- [ ] I have added audit logs for new operation logic (if applicable) — N/A
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A
