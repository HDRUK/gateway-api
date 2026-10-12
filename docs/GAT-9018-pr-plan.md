# GAT-9018 — GWDM 3.0 branch: PR breakdown & implementation plan

Branch: `feat/GAT-9018` → base `dev`. Large branch (~5,200 insertions) introducing
GWDM 3.0 SQL persistence + a version-handler hierarchy. This doc splits it into
ordered, independently-reviewable PRs, and records the review fixes **already applied
to the working tree** (so the implementing session doesn't redo them).

---

## 0. Current working-tree state (fixes ALREADY applied — do not redo)

A senior review (see "Review findings" below) surfaced 2 Critical + 4 High issues.
**All 6 are already fixed in the uncommitted working tree.** When splitting into PRs,
carry these changes into the PR indicated in the "→ PR" column.

| ID | Summary | Files changed | → PR |
|----|---------|---------------|------|
| **C2** | `GwdmVersionContext::requestedVersion()` reverted to return `null` on absent `x-gwdm-version` header (was wrongly returning config fallback → stale/500 reads on the V3 default path) | `app/Context/GwdmVersionContext.php` | PR2 |
| **C1** | ES indexing read sites now reconstruct the full envelope via a new `reconstructedLatestEnvelope()` helper → `DatasetService::getReconstructedMetadataEnvelope(...)`. Fixes 3.0 (no `metadata` key) **and** the latent 2.x bug where a delta *latest* row stored `metadata=[]` → blank search docs. 5 sites fixed: `reindexElastic`, `indexElasticDataProviderColl`, `indexElasticPublication`, tool indexer, `checkingDataset`. | `app/Http/Traits/IndexElastic.php` | PR2 (delta fix) — also unblocks PR5 backfill |
| **H1** | Linkage extraction no longer reads the raw `metadata` column. `Gwdm2xHandler::extractLinkages()` now reconstructs the GWDM (fixes 2.x delta rows wiping linkage on every update) via new protected `writeLinkages(dv, gwdm)`. `Gwdm30Handler` writes linkage junctions **synchronously in `afterStore()`** from the input metadata (they are unrecoverable later — see gotcha #2) and overrides `extractLinkages()` to a no-op. | `app/Services/Gwdm/Gwdm2xHandler.php`, `app/Services/Gwdm/Gwdm30Handler.php`, `tests/Feature/DatasetVersionGwdm30Test.php` (3 linkage tests now drive `afterStore`) | 2.x part → PR2; 3.0 part → PR4 |
| **H2** | Version-row insert + `afterStore()` (3.0 SQL section writes) now wrapped in a single `DB::transaction()` in `create()`, `persistMetadataVersion()`, `persistMetadataVersionLegacy()` → no orphaned partial 3.0 versions. | `app/Services/DatasetService.php` (added `use Illuminate\Support\Facades\DB;`) | PR2 (harmless no-op for 2.x; meaningful once PR4 lands) |
| **H3** | `update()` (V3) re-enabled `dispatchJobs()` (was commented out) so `LinkageExtraction` + `TermExtraction` run on V3 updates. Passes the prepared `$gwdm` (not the input wrapper) to match `create()`/`updateV2()` TED payload shape. NB: `dispatchJobs` only fires Linkage+Term; the observer fires Extract*+Index — they are **complementary, not duplicated**. | `app/Services/DatasetService.php` | PR2 |
| **H4** | `persistMetadataVersion()` forces a full snapshot when the target `gwdm_version` differs from the previous version's `gwdm_version`, keeping every delta window schema-homogeneous. | `app/Services/DatasetService.php` | PR2 |

**Verification done:** `php -l` clean on all; phpstan level 3 adds **no** new errors
(pre-existing dynamic-property noise only); `tests/Feature/DatasetVersionGwdm30Test.php`
= 22 passed / 1 failed. The 1 failure (`persist populates all sql tables`, line ~82,
`access_rights` value mismatch) is **pre-existing/WIP in `Gwdm30Handler::persist`** —
confirmed identical with changes stashed. Fix it in **PR4**.

> `DatasetService.php` also carries a large **pre-existing Pint reformat** the user
> started before this work — that noise is expected and belongs with PR2.

---

## 1. PR breakdown (ordered)

Each PR is independently reviewable. Hard dependencies noted. Rule of thumb:
**migrations that a handler writes to must land before (or with) that handler.**

### PR1 — `gwdm_version` column (schema + model)
- `database/migrations/2026_06_17_000001_add_gwdm_version_to_dataset_versions_table.php`
- `app/Models/DatasetVersion.php` (`$fillable` + reduced-relation `selectRaw` change)
- `app/Jobs/LinkageExtraction.php` gwdm_version resolution (reads the indexed column)
- **Risk:** low, no behavioural change. **Must land first** — everything reads/writes this column.

### PR2 — Version context + handler hierarchy + DatasetService refactor (2.x only)
The big reviewable refactor. Behaviour-preserving for 2.x.
- Context: `app/Context/GwdmVersionContext.php` (**with C2 fix**), `OutputSchemaContext.php`,
  `PartnerContext.php`, `app/Http/Middleware/ResolveGwdmVersionContext.php` + `Kernel.php`,
  `config/partners.php`
- Handlers (no 3.0 arm yet): `GwdmHandlerFactory` (drop the `'3.0'` match arm + drop `'3.0'`
  from `SUPPORTED_VERSIONS` for this PR), `GwdmMetadataHandler`, `Gwdm1xHandler`,
  `Gwdm2xHandler` (**incl. H1 reconstruct + `writeLinkages`**), `Gwdm20Handler`, `Gwdm21Handler`
- `DatasetService` delegation refactor (**incl. H2 transactions, H3 dispatch, H4 snapshot-on-schema-change**),
  `LinkageExtraction` slimming into `Gwdm2xHandler`
- `IndexElastic` **C1** reconstruction helper (fixes 2.x delta indexing here; also correct for 3.0 later)
- **Depends on:** PR1.

### PR3 — gwdm30 tables + models (schema only)
- `app/Models/Gwdm30/*` (16 models)
- `database/migrations/2026_06_17_000003…` + all `create_gwdm30_*` migrations
- **Consolidate first** (see M3): the ~20 migrations are iterative dev residue
  (`create_*` + `add_missing_cols_*` + `add_dcat_fields_*`, many dated `2026_06_23`).
  If they have **not** run in any shared env, squash into one clean create-migration per
  table. If they **have** run somewhere, leave them and note it in the PR.
- No behaviour on its own. **Depends on:** PR1. Reviewable in parallel with PR2, but must precede PR4.

### PR4 — GWDM 3.0 handler + activation
- `Gwdm30Handler` (**incl. H1 afterStore-writes-linkage + extractLinkages no-op**),
  factory `'3.0'` arm, `'3.0'` back into `SUPPORTED_VERSIONS`
- `Api/V3/DatasetController.php` version endpoints, `config/routes_v3.php`
- `tests/Feature/DatasetVersionGwdm30Test.php`, `tests/Unit/test_files/gwdm_v3p0_dataset_min.json`,
  `tests/Traits/MockExternalApis.php`
- **Must also fix in this PR:** the pre-existing `persist` failure (line ~82), **M1** (double-storage
  of PII in patch JSON + SQL — skip patch computation for 3.0 or document erasure), **M2** (call
  `preloadSections()` before `afterRead()` in `validateAllVersions()` loop to avoid ~17×N queries).
- **Cannot be split further:** enabling `x-gwdm-version: 3.0` before the tables/handler exist 500s.
- **Depends on:** PR2 + PR3.

### PR5 — Backfill command + deploy workflow
- `app/Console/Commands/BackfillGwdm30.php`, `.github/workflows/deployment.yaml`
- **Must not merge until C1 is in `dev`** (via PR2) — otherwise running the backfill makes a 3.0
  row the latest version of every ACTIVE dataset and strips search titles/abstracts platform-wide.
- Ship last; run `--dry-run` first. Add tests: idempotency (re-run = no-op), `--force`, id-range resume, dry-run writes nothing.
- **Depends on:** PR4.

---

## 2. Implementation gotchas discovered this session

1. **`afterCommit` on jobs is NOT viable here.** Tests use `Tests\Traits\RefreshDatabaseLite`,
   which wraps each test in `DB::beginTransaction()` and rolls back in `tearDown()`. Setting
   `public $afterCommit = true` on the enrichment/index jobs would mean they **never dispatch
   under tests** (the outer test transaction never commits). H2 therefore uses a plain
   `DB::transaction()` wrap only. The residual "jobs dispatched before commit on rollback"
   concern is a separate, lower-priority follow-up — do **not** solve it with `afterCommit`.
   (My `DB::transaction()` wraps nest safely as savepoints under the test transaction.)

2. **3.0 linkage has no durable home except the junction tables.** `Gwdm30Handler::persist()`
   stores only the linkage *meta* subset (`isGeneratedUsing`, `tools`, …) in `LinkageMeta`.
   The `datasetLinkage` / `publicationAboutDataset` / `publicationUsingDataset` arrays are **not**
   persisted, and `readLinkages()` rebuilds those sections **from the junction tables**. So the
   async `LinkageExtraction` job cannot recover them (it would read them back from itself). That's
   why 3.0 writes junctions **synchronously in `afterStore()`** and no-ops the async path.

3. **The `x-gwdm-version` read/write split.** `targetVersion()` (write) falls back to config;
   `requestedVersion()` (read) returns `null` on absent header so the caller uses the genuine
   latest version. Keep them distinct — collapsing them re-introduces C2.

4. **Keep diffs minimal — beware Pint noise.** `IndexElastic.php` and the Gwdm handlers were
   committed in an *aligned-arrow* style that is **not** Pint-clean. Running `vendor/bin/pint`
   on them reformats hundreds of pre-existing lines and drowns the functional change. Apply
   functional edits in the file's existing style; only Pint files the user is already converting
   (`DatasetService.php`).

---

## 3. Remaining review findings (Medium / Low) — schedule into PRs

- **M1** (→PR4) 3.0 double-stores metadata (RFC-6902 patch JSON *and* SQL); PII survives in the
  patch column after SQL-row erasure. Skip patch computation for 3.0, or include the patch column in erasure.
- **M2** (→PR4) N+1 in `validateAllVersions()`/`/metadata-versions`: `afterRead()` per row without
  `preloadSections()` → ~17×N queries for 3.0.
- **M3** (→PR3) Consolidate the fragmented gwdm30 migrations (if not yet run anywhere).
- **M4** (→PR3 or PR5) `2026_06_23_000009_backfill_gwdm30_distributions.php` runs at migrate time when
  `gwdm30_accessibility` is still empty → permanent no-op where it sits. Move into the artisan command.
- **M5** (→PR2) `mapCoverage` accumulation: coverage pivot rows now accumulate per version. Decide
  per-version vs unioned semantics; resolve the `DISCUSSION POINT FOR REVIEW` comment block before merge.
- **L1** docblock on `requestedVersion()` (fixed with C2). **L2** stray `/**` + large discussion comment
  in `DatasetService`. **L3** non-sargable DOI `whereRaw` in `Gwdm2xHandler::findTargetPublication`.
  **L4** `Dataset::latestVersionID()` raw `DB::select` → Eloquent. **L5** `Gwdm30Handler` 939 lines —
  consider `Gwdm30Writer`/`Gwdm30Reader` split. **L6** `persist()` creates all-null single-row sections
  even when the source section is absent.

---

## 4. Test-coverage gaps to add (mostly PR4)
1. End-to-end through `DatasetService::create()`/`update()` for a 3.0 dataset (real `buildEnvelope`,
   observer, reconstruction) — would have caught C1, H1, H2.
2. ES indexing of a 3.0 dataset asserts non-empty title/abstract (C1).
3. Header-absent read against a dataset whose latest version is 3.0 (C2).
4. 3.0 delta reconstruction across a window (2→9) and a mixed 2.1→3.0 chain (H4).
5. Backfill idempotency / `--force` / id-range resume / dry-run (PR5).
6. V3 update populates linkage junctions (H3).
