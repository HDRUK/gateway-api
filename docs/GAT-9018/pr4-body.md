## Screenshots (if relevant)

N/A.

## Describe your changes

**PR 4 of 5** — stacked on PR3. **Activates GWDM 3.0.**

- **`Gwdm30Handler`** — persists sections to the `gwdm30_*` tables (`afterStore`/`persist()`), reconstructs from them (`afterRead`), writes linkage junctions synchronously.
- **`GwdmHandlerFactory`** — restores the `'3.0'` arm + `SUPPORTED_VERSIONS` entry (removed in PR2).
- **V3 version endpoints** — `config/routes_v3.php` + `Api/V3/DatasetController`.
- Test fixtures + `MockExternalApis` 3.0 support.

### Reviewer notes
- The one pre-existing `persist` test failure (stale scalar assertions on `ensureArray()`-normalised, array-cast fields — `accessRights`, `jurisdiction`, `vocabularyEncodingSchemes`, `conformsTo`, `languages`, `keywords`, `datasetType`, origin `purpose`/`source`) is **fixed**: those scalar source values correctly round-trip as single-element arrays.
- **M1 (fixed)**: `persistMetadataVersion()` now forces a full snapshot for GWDM 3.0 (`patch = null`) — 3.0 content lives in SQL, so a delta patch would only duplicate section values (incl. PII) into the immutable patch column where they'd survive SQL-row erasure.
- **M2 (fixed)**: `Gwdm30Handler::preloadSectionsForVersions()` loads each SQL section once across all version ids; `validateAllVersions()` calls it up front so the per-row `afterRead()` no longer issues ~16 queries per 3.0 row. New test asserts the batched query count.
- **phpstan (fixed)**: `@property`/`@property-read` docblocks on the Gwdm30 models + `DatasetVersion` (the dynamically-set `gwdm30*` relations) clear the level-3 dynamic-property findings. The full stack is phpstan-clean.

## Issue ticket link

GAT-9018

## Environment / Configuration changes (if applicable)

`x-gwdm-version: 3.0` becomes an accepted request header once this PR is deployed. GWDM 3.0 must not be enabled before PR3's tables exist (why this PR is stacked on PR3).

## Requires migrations being run?

No new migrations here (the `gwdm30_*` tables land in PR3), but this PR is inoperable without them.

## If not using the pre-push hook. Confirm tests pass:

- `pest tests/Feature/DatasetVersionGwdm30Test.php` — 23 passed (handler persist/read for every section).
- `GwdmHandlerFactoryTest` updated to assert `'3.0'` resolves to `Gwdm30Handler` and is in `SUPPORTED_VERSIONS`.
- Full stack suite green: **80 passed** across the GWDM/dataset feature tests.

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [x] I have created mocks for unit tests (where appropriate)
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A
- [ ] I have added Swagger annotations for new endpoints (if applicable) — V3 version endpoints; annotate if the team requires
- [ ] I have added audit logs for new operation logic (if applicable) — N/A
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A
