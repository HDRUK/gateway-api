# Risk Assessment: Migrating `FORM_HYDRATION_SCHEMA_LATEST_VERSION` 4.0.0 → 4.1.0

**Status:** Draft for review
**Date:** 2026-09-10
**Author:** Calum Macdonald
**Scope:** HDRUK schema 4.0.0 → 4.1.0 uplift, associated GWDM target version change, and the data-loss exposure created by the v2 dataset update endpoint.

---

## 1. Executive summary

The 4.1.0 uplift is, in isolation, low risk — it is an additive schema change. The risk does **not** come from the schema. It comes from the write path we will use to populate the new fields.

The confirmed position is:

> **`PUT /api/v2/teams/{teamId}/datasets/{id}` — the endpoint the onboarding form uses — overwrites the existing `dataset_versions` row in place. It does not create a new version. The pre-edit metadata is destroyed on save and is not recoverable from the application database.**

Because the plan is to hand-edit existing records through the onboarding form to add DUO codes, every edited dataset will have its original 4.0.0 / GWDM 2.0 metadata permanently overwritten at the moment of save. There is no in-app rollback.

**Overall risk: HIGH likelihood, HIGH impact, but cheaply mitigated.**

The recommended mitigation is *not* to switch versioning on as part of this piece of work. It is to take a scoped, restorable backup of the affected rows immediately before the editing campaign. Turning versioning on is a larger change with its own unresolved linkage problem (section 4) and should be treated as separate work.

---

## 2. The intended change

| | Current | Target |
|---|---|---|
| Form hydration schema | 4.0.0 | 4.1.0 |
| GWDM storage version | 2.0 | 2.2 (2.1 exists but is unused) |
| New fields | — | DUO codes (optional), plus `conformsTo` field and enum changes |

The intended sequence for existing data:

1. Stored GWDM 2.0 is read and translated up to 4.0.0 for the landing pages.
2. Post-uplift, that same record is loaded into the form as 2.0 → 4.0.0 → 4.1.0, with the new DUO code fields empty.
3. An operator manually populates the DUO codes.
4. On save, the record is translated 4.1.0 → GWDM 2.2 and written back.

Step 4 is where the data loss occurs.

---

## 3. Confirmed behaviour of the v2 write path

### 3.1 Route resolution

`config/routes_v2.php:541` maps `PUT /teams/{teamId}/datasets/{id}` to `Api\V2\TeamDatasetController@update`. Note this is *not* `Api\V2\DatasetController@update` — that handles the non-team-scoped `/datasets/{id}` route and takes a different (though behaviourally identical) code path.

### 3.2 The overwrite

`TeamDatasetController@update` (`app/Http/Controllers/Api/V2/TeamDatasetController.php:609`) reads the current version number at line 697 purely to build the audit log string, then calls `updateMetadataVersion()` from the `MetadataVersioning` trait.

`app/Http/Traits/MetadataVersioning.php:116`:

```php
$dv = DatasetVersion::where([
    'dataset_id' => $currDataset->id,
    'version'    => $currDataset->lastMetadataVersionNumber()->version,
])->first();

$dv->metadata    = json_encode($metadataSaveObject);
$dv->title       = $newMetadata['summary']['title'] ?? null;
$dv->short_title = $newMetadata['summary']['shortTitle'] ?? ($newMetadata['summary']['title'] ?? null);
$dv->save();
```

It selects the latest version row and mutates it. There is no `create()` and no `version + 1`. For a single-version dataset the `version: 1` row is updated in place, its `id` is unchanged, and the previous `metadata` JSON is gone.

### 3.3 Both v2 paths behave the same way

`DatasetService::updateV2()` (`app/Services/DatasetService.php:487`), used by the non-team-scoped v2 route, delegates to `persistMetadataVersionLegacy()` at line 595, which performs the same select-latest-and-overwrite. Its docblock is explicit:

> V2 (legacy) update — overwrites the existing version row in place. Preserved for backwards compatibility with third-party integrations that rely on the v2 API.

So the behaviour is deliberate and documented, not a defect. It is simply incompatible with a bulk manual re-editing campaign.

### 3.4 v3 does version correctly

`DatasetService::update()` (line 402) calls `persistMetadataVersion()` (line ~640), which creates a new row at `version + 1`, storing either a full snapshot or an RFC 6902 delta in `patch`. Routes already exist:

- `PUT /api/v3/datasets/{id}`
- `PUT /api/v3/teams/{teamId}/datasets/{id}`

One helpful detail: `persistMetadataVersion()` **forces a full snapshot when the GWDM version changes** between consecutive versions, because a delta computed across two different schemas would be meaningless. A 2.0 → 2.2 transition would therefore be stored as a complete snapshot, not a patch — which is exactly the behaviour we would want here.

---

## 4. Why versioning was turned off, and what it would take to turn it back on

### 4.1 The stated reason was storage, not linkages

Commit `d644bed3` (25 Oct 2024, Loki Sinclair):

> *removes dataset metadata versioning until we have time to think about the sheer size impact structural metadata is having*

The driver of record was **table growth**. Structural metadata (table/column/value definitions) is by far the largest part of a GWDM payload, and under the old scheme every update duplicated the entire envelope. There was no delta mechanism at the time — that only arrived with `4cfc1868` (GAT-7181, Mar 2026), which introduced the v3 JSON Patch approach specifically to solve the size problem.

### 4.2 The linkage concern is real, but it is a separate defect

The recollection that linkages were being lost is well founded, and the removed code shows why. The old `addMetadataVersion()` carried forward exactly one relation type:

```php
$dv = DatasetVersionHasDatasetVersion::where([
    'dataset_version_source_id' => $currDataset->latestVersionID($currDataset->id),
    'linkage_type' => DatasetVersionHasDatasetVersion::LINKAGE_TYPE_DATASETS,
])->get();
```

Every other pivot keyed on `dataset_version_id` was silently orphaned when a new version row was created:

| Pivot table | Carried forward by old code? |
|---|---|
| `dataset_version_has_dataset_version` | Yes (datasets linkage type only) |
| `collection_has_dataset_version` | **No** |
| `dur_has_dataset_version` | **No** |
| `publication_has_dataset_version` | **No** |
| `dataset_version_has_tool` | **No** |
| `dataset_version_has_named_entities` | **No** |
| `dataset_version_has_spatial_coverage` | **No** |
| `enquiry_thread_has_dataset_version` | **No** |

So both accounts are correct: the *commit* was justified on storage grounds, but the partial-carry-forward bug meant versioning was also visibly breaking collections, DURs and publications. Reverting to that behaviour would reintroduce both problems.

### 4.3 v3 only partially solves this

v3 fixes the storage problem via deltas, and it rebuilds the *metadata-derived* links on each new version:

- `DatasetService::dispatchJobs()` dispatches `LinkageExtraction` (rebuilds `dataset_version_has_dataset_version`) and `TermExtraction` (rebuilds `named_entities`).
- `DatasetVersionObserver::created()` dispatches `ExtractToolsFromMetadata`, `ExtractPublicationsFromMetadata` and `ExtractProjectGrantsFromMetadata`.
- `persistMetadataVersion()` calls `mapCoverage()` for spatial coverage.

But the **user-curated** links are still not carried forward. A grep for `CollectionHasDatasetVersion`, `DurHasDatasetVersion`, `PublicationHasDatasetVersion`, `DatasetVersionHasTool` and `EnquiryThreadHasDatasetVersion` across `DatasetService` and `app/Http/Controllers/Api/V3/` returns nothing.

That means **switching to v3 today would still drop collection membership, DUR associations and enquiry threads** on every update. These are curated by humans, not derivable from metadata, so no extraction job can rebuild them.

This is the single biggest reason not to bundle "turn versioning back on" into this schema uplift.

---

## 5. Risk register

| # | Risk | Likelihood | Impact | Rating |
|---|---|---|---|---|
| R1 | Original 4.0.0 / GWDM 2.0 metadata destroyed on manual DUO edit; no rollback | **Certain** | High | **HIGH** |
| R2 | Curated linkages lost if versioning is enabled without a carry-forward fix | High *(if v3 adopted)* | High | **HIGH** (avoidable) |
| R3 | `conformsTo` enum changes reject or silently drop values on existing records | Medium | Medium | **MEDIUM** |
| R4 | Operator error during manual DUO entry, compounded by R1 | Medium | Medium | **MEDIUM** |
| R5 | Table growth if full versioning re-enabled without deltas | Low *(v3 has deltas)* | Medium | **LOW** |
| R6 | v3 routes carry `sanitize.input`, v2 has it commented out — payload may not survive | Medium | Low | **LOW** |

On R3: the uplift is described as additive and therefore backwards compatible, but the `conformsTo` change and its enum revisions are the part that is *not* purely additive. This needs explicit validation against a representative sample before the campaign, not just a theoretical compatibility argument.

On R6: `config/routes_v2.php:548` has `sanitize.input` commented out, whereas the v3 route definitions include it. Any move to v3 needs the metadata payload tested against sanitisation first.

---

## 6. Recommended mitigations

### 6.1 Do this before the editing campaign (essential)

**M1 — Snapshot the affected rows.** Before any operator touches the form, take a restorable copy of every `dataset_versions` row in scope:

```sql
CREATE TABLE dataset_versions_backup_pre_410 AS
SELECT * FROM dataset_versions
WHERE dataset_id IN (/* scoped list */);
```

This is cheap, reversible, and removes R1 almost entirely. Retain it until the campaign is signed off. Record the row count and a checksum so the restore can be verified.

**M2 — Validate the translation round trip on a sample.** Take a representative set of existing 2.0 records, run 2.0 → 4.0.0 → 4.1.0 → 2.2 in a non-production environment, and diff against the original. This is the only way to get real evidence for the "backwards compatible in theory" claim, and it directly tests R3.

### 6.2 Do this during the campaign

**M3 — Stage the rollout.** Edit a small first tranche, verify the landing pages render correctly and the DUO codes persist, then continue. Do not edit the full estate in one pass.

**M4 — Keep an operator log.** Record which dataset IDs were edited and when, so the backup can be selectively restored rather than wholesale.

### 6.3 Treat as separate work

**M5 — Re-enabling versioning.** This should be its own ticket. The prerequisite is a carry-forward routine for the curated pivots listed in section 4.2, applied inside the same transaction as the new version row. Without that, enabling versioning trades a known data-loss problem for a less visible one. The v3 delta mechanism has already solved the storage objection from 2024, so the remaining blocker is genuinely just linkage carry-forward.

---

## 7. Recommendation

Proceed with the 4.1.0 uplift, but **do not** attempt to re-enable versioning as part of it.

The combination of M1 (backup) and M2 (validated round trip) reduces the headline risk from "irreversible data loss across the estate" to "recoverable, on a verified translation path" at a cost of roughly half a day.

Re-enabling versioning is the right long-term answer and the v3 machinery is most of the way there — but shipping it alongside a manual data-entry campaign would mean debugging linkage loss and schema translation at the same time, on live records, with no rollback. Sequence them.

---

## 8. Open questions

1. How many datasets are in scope for the manual DUO edit? This determines whether M1 is a table copy or something more targeted.
2. Are any in-scope datasets currently members of collections or linked to DURs? If so they are exactly the records that would be damaged by a premature v3 switch.
3. Is 2.2 the right target, or should the unused 2.1 be adopted instead? Introducing 2.2 while 2.1 remains dead code adds a version to the matrix for no obvious gain — worth a sentence of justification in the ticket.
4. What is the exact set of `conformsTo` enum changes? R3 cannot be sized without it.
5. Who signs off that the campaign is complete, and at what point is the backup table dropped?
