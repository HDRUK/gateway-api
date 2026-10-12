## Screenshots (if relevant)

_N/A — backend-only change (indexing, metadata retrieval, DB index)._

## Describe your changes

Fixes Elasticsearch indexing for **delta (non-snapshot) dataset version rows** and harmonises metadata field retrieval so it always flows through the GWDM handlers rather than scattered `version_compare()` checks.

- **Delta-row indexing + GWDM elastic gating** (`IndexElastic`, `DatasetVersion`): delta rows carry no `metadata` column, so they were previously unindexable. They are now reconstructed from the nearest snapshot before indexing, and Elastic indexing is gated on the row's own GWDM version.
- **Harmonised field retrieval into the GWDM handlers** (`GwdmMetadataHandler`, `Gwdm1xHandler`, `Gwdm2xHandler`): material-types and related field-extraction logic moved out of `DatasetHydrator`/jobs and into version-specific handlers, resolved via `GwdmHandlerFactory`. Callers now pass the row's `gwdm_version` and let the handler decide.
- **Reconstructed-metadata read path** (`DatasetService`, `DatasetHydrator`, `ExtractToolsFromMetadata`, `ExtractPublicationsFromMetadata`): reads go through `getReconstructedMetadataEnvelope()`, passing the already-loaded version row as `prefetched` so snapshots stay query-free and deltas rebuild from their snapshot.
- **Perf — N+1 fix** (`IndexElastic`, `DatasetHydrator`): batch dataset lookups instead of one query per hit; reconstruct each dataset's metadata once per request.
- **Supporting DB index**: composite index `(dataset_id, deleted_at, version)` on `dataset_versions` so latest-version / index-eligible lookups become an index-only seek instead of a per-row dependent subquery.

## Issue ticket link

- https://hdruk.atlassian.net/browse/GAT-9246 (branch)
- Related: GAT-9018 (delta indexing / GWDM gating / material-types refactor commits)

## Environment / Configuration changes (if applicable)

None. No new environment variables; `.env.example` unchanged.

## Requires migrations being run?

**Yes.** One new migration adds a composite index to `dataset_versions`:
`2026_07_27_000001_add_composite_index_to_dataset_versions_table.php`
(`dataset_versions_dataset_deleted_version_index` on `dataset_id, deleted_at, version`). Index-only — no data changes; safely reversible via `down()`.

## If not using the pre-push hook. Confirm tests pass:

```bash
composer run pest
composer run phpstan
```

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [x] I have created mocks for unit tests (where appropriate)
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A, covered by feature/unit tests
- [ ] I have added Swagger annotations for new endpoints (if applicable) — N/A, no new endpoints
- [ ] I have added audit logs for new operation logic (if applicable) — N/A, no new user-facing operations
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A, no new env vars
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A, no new env vars

<!--
New tests added in this PR:
  tests/Feature/DatasetSpatialCoverageTest.php
  tests/Feature/DatasetVersionSearchableTest.php
  tests/Feature/GwdmHandlerElasticFieldsTest.php
  tests/Feature/GwdmHandlerMaterialTypesTest.php
  tests/Feature/IndexElasticDeltaRowTest.php
  tests/Feature/IndexElasticGwdmGatingTest.php
  tests/Feature/Jobs/ExtractMetadataDeltaRowTest.php
  tests/Unit/DatasetHydratorTest.php (expanded)
-->

## AI disclosure

Parts of this PR were developed with the assistance of AI tooling (Claude Code). All AI-generated code and tests have been reviewed, understood, and verified by the author, who takes full responsibility for the changes.
