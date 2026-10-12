## Screenshots (if relevant)

N/A.

## Describe your changes

Two related changes to how 2.x dataset linkages are **read back**, keeping all linkage logic inside the GWDM handler:

### 1. Move linkage *resolution* (read) onto the GWDM handler

Linkage handling was split across two layers. The **write** path already lived on the handler (`Gwdm2xHandler::extractLinkages()` → `writeLinkages()` → junction tables), but the **read** path was stranded in `DatasetService` — `afterRead()` reached *back* into the service, and a second `DatasetService::getLinkages()` re-queried the same tables independently.

This PR gives read/write full symmetry on the handler:

- New `Gwdm2xHandler` methods — `resolveDatasetLinkages()` / `resolvePublicationLinkages()` (the SQL source of truth, `protected`), `getLinkages()` (public, flat frontend attribute), and the `latestShortTitlesFor()` helper.
- `afterRead()` now calls `$this->resolveDatasetLinkages()` / `resolvePublicationLinkages()` directly — the callback into `DatasetService` for linkage reads is gone.
- `GwdmMetadataHandler` gains a no-op `getLinkages()` default so non-2.x versions (e.g. 1.x) are safe.
- Removed `DatasetService::getLinkages()`; `prepareForShow()` now resolves the latest version's handler (off its `gwdm_version`) to build the flat `linkages` attribute.

Net effect: a future version-specific handler can override extraction, resolution, **and** the reconstructed metadata block in one place. `extractLinkages()` still uses `DatasetService::getReconstructedMetadataEnvelope()` — that's delta/snapshot reconstruction, a genuine service responsibility, not a linkage concern.

### 2. Resolved linkage title tracks the target's *latest* version (GAT-9222)

Resolved dataset linkages store a frozen `dataset_version_target_id` (the target's latest version *at extraction time*). On read-back the two halves of a link disagreed:

- the **URL** is built from the dataset id (`/en/dataset/{id}`), which the frontend resolves to the target's **current latest** version;
- the **title** was sourced from the **frozen** target version's `short_title`.

Once the target dataset gains a newer version these diverge — the URL points at the current dataset while the title is stale. `resolveDatasetLinkages(useLatestTitle: true)` now sources the title from the target dataset's latest version (via `latestShortTitlesFor()`, reusing `Dataset::latestMetadata()` — `latestOfMany('version')`, soft-delete aware), falling back to the frozen title if the lookup misses. Applied on both read paths (`getLinkages()` and `afterRead()`).

### Also in this diff

- `writeLinkages()` omission semantics: an **absent** `linkage` key now leaves existing junction rows untouched (partial update), while a **present-but-empty** sub-array still clears — previously an omitted key was treated as a clear.

### Reviewer notes

- `latestShortTitlesFor()` loads `latestMetadata` in full (not a column-limited eager load) — under `latestOfMany`'s internal joins a `latestMetadata:id,…` selection is ambiguous.
- The stored JSON-blob `linkage` section is retained deliberately as the **async extraction seed** and the **delta diff base** — SQL is authoritative on read via `afterRead()`. It must not be "tidied away".

## Issue ticket link

https://hdruk.atlassian.net/browse/GAT-9222

## Environment / Configuration changes (if applicable)

None.

## Requires migrations being run?

No — no schema changes; this PR only touches service/handler read logic and its test, reading/writing the existing linkage junction tables.

## If not using the pre-push hook. Confirm tests pass:

- `pest tests/Feature/DatasetVersionLinkageTest.php` — **6 passed (35 assertions)**:
  - `test_resolved_linkage_title_tracks_target_latest_version` (regression: target gains a newer version with a changed `short_title`; both `getLinkages()` and the `afterRead()`-driven reconstructed envelope return the new title)
  - `getLinkages()` and `afterRead()` resolve the same dataset set
  - archived / non-ACTIVE linkage targets excluded from both paths
  - `afterRead()` returns empty when no extracted rows exist (legacy JSON-blob fallthrough)
  - `writeLinkages()` preserves rows on an omitted `linkage` key, and clears them on an explicitly emptied one
- `pest tests/Feature/V2/DatasetTest.php` — 14 passed; `pest tests/Feature/V2/DatasetVersioningTest.php` — 5 passed (unaffected read/write paths still green).
- `phpstan` level 3 — no errors.
- No new Pint violations introduced.

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [x] I have created mocks for unit tests (where appropriate) — TRASER/MMC via `MockExternalApis`
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A
- [ ] I have added Swagger annotations for new endpoints (if applicable) — no endpoints
- [ ] I have added audit logs for new operation logic (if applicable) — N/A
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A
