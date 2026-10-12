# Dataset Metadata Flow

How metadata is ingested, translated, versioned, and persisted to `datasets` + `dataset_versions`.

```mermaid
flowchart TD
    subgraph ENTRY["API Entry Points"]
        V1["POST /v1/datasets\nV1 DatasetController::store()\nPUT /v1/datasets/{id}\nV1 DatasetController::update()"]
        V2C["POST /v2/datasets\nV2 DatasetController::store()"]
        V2T["POST /v2/teams/{id}/datasets\nV2 TeamDatasetController::store()\nPUT /v2/teams/{id}/datasets/{id}\nV2 TeamDatasetController::update()"]
        V3["PUT /v3/teams/{id}/datasets/{id}\nV3 DatasetController::update()"]
    end

    subgraph PROCESSING["Metadata Processing Layer"]
        ONBOARD["MetadataOnboard trait\napp/Http/Traits/MetadataOnboard.php\nmetadataOnboard()"]
        VERSIONING_TRAIT["MetadataVersioning trait\napp/Http/Traits/MetadataVersioning.php\naddMetadataVersion() / updateMetadataVersion()"]
        SVC_C["DatasetService::create()\napp/Services/DatasetService.php"]
        SVC_U["DatasetService::update()\napp/Services/DatasetService.php"]
        TRASER["TRASER HTTP call\nTranslates arbitrary schema → GWDM\nReturns { type, version, metadata }"]
        ENVELOPE["Build metadata envelope\n{\n  gwdmVersion: 'x.y.z',\n  metadata: { ...GWDM translated... },\n  original_metadata: { ...raw input... }\n}"]
    end

    subgraph VERSIONING["DatasetVersion Persistence Strategy"]
        DECIDE{{"V3 delta versioning?\nDatasetService::update only"}}
        SNAPSHOT["SNAPSHOT version\ndataset_versions.metadata = full envelope\ndataset_versions.patch = NULL\n— v1 and every 10th version —"]
        DELTA["DELTA version\ndataset_versions.metadata = reduced envelope\n  { gwdmVersion, original_metadata }\ndataset_versions.patch = RFC 6902 patch array\n— all other versions —"]
        LEGACY["LEGACY overwrite (V1 / V2)\nSingle DatasetVersion row updated in place\nfull envelope always stored\nno patch column used"]
    end

    subgraph DB["Database Tables"]
        DS["datasets\n─────────────\nid, pid, team_id, user_id\nstatus, create_origin\nactive_date, partner_context"]
        DV["dataset_versions\n─────────────\nid, dataset_id, version\nmetadata JSON\npatch JSON nullable\ntitle, short_title"]
    end

    subgraph OBSERVERS["Observer → Async Jobs"]
        OBS_DV["DatasetVersionObserver\ncreated / updated / deleted\napp/Observers/DatasetVersionObserver.php"]
        J_IDX["IndexDataset / ReindexDataset / DeindexDataset\nSync to Elasticsearch"]
        J_PUB["ExtractPublicationsFromMetadata\nExtractToolsFromMetadata\nLink related entities"]
        J_LINK["LinkageExtraction\nTermExtraction\nEnrich search index"]
        ELASTIC["Elasticsearch index"]
    end

    V1 --> ONBOARD
    V2C --> SVC_C
    V2T --> ONBOARD
    V3 --> SVC_U

    ONBOARD --> TRASER
    SVC_C --> TRASER
    SVC_U --> TRASER

    TRASER -->|"original_metadata = raw input\nmetadata = gwdm output"| ENVELOPE

    ENVELOPE --> VERSIONING_TRAIT
    ENVELOPE --> SVC_C
    ENVELOPE --> DECIDE

    DECIDE -->|"version == 1 or version % 10 == 0"| SNAPSHOT
    DECIDE -->|"all other versions"| DELTA

    VERSIONING_TRAIT --> LEGACY

    SNAPSHOT --> DV
    DELTA --> DV
    LEGACY --> DV

    DV --> DS

    DV -->|"Eloquent lifecycle event"| OBS_DV
    OBS_DV --> J_IDX
    OBS_DV --> J_PUB
    OBS_DV --> J_LINK
    J_IDX --> ELASTIC
```

## Key source files

| File | Role |
|---|---|
| `app/Services/DatasetService.php` | V3 delta versioning via `persistMetadataVersion()`; assigns `original_metadata` before TRASER call |
| `app/Http/Traits/MetadataOnboard.php` | V1/V2 create path — calls TRASER, builds envelope, creates `DatasetVersion` |
| `app/Http/Traits/MetadataVersioning.php` | V1/V2 update path — `addMetadataVersion()` / `updateMetadataVersion()` overwrites version row |
| `app/Models/DatasetVersion.php` | Custom `metadata` Attribute accessor handles double-JSON-encoding edge case |
| `app/Observers/DatasetVersionObserver.php` | Triggers Elastic indexing + extraction jobs on every version save |
| `database/migrations/2026_03_11_133600_add_patch_to_dataset_versions_table.php` | Introduces `patch` column and makes `metadata` nullable for delta versioning |

## Envelope shape

All three API versions store metadata in `dataset_versions.metadata` as a JSON envelope with this top-level structure:

```json
{
  "gwdmVersion": "2.0.0",
  "metadata": { },
  "original_metadata": { }
}
```

- **`gwdmVersion`** — the GWDM schema version the translated `metadata` conforms to
- **`metadata`** — GWDM-translated output from TRASER; omitted in delta versions (reconstructed by applying the `patch`)
- **`original_metadata`** — the raw input as submitted by the caller before TRASER translation; always present

See `docs/metadata-snapshot-example.json` and `docs/metadata-delta-example.json` for concrete examples.

## Delta reconstruction

To read the full metadata for any version:

1. Find the nearest snapshot at or below the target version (where `patch IS NULL`)
2. Apply each subsequent delta's `patch` (RFC 6902) in version order to the snapshot's `metadata`
3. This is handled by `DatasetService::getReconstructedMetadataEnvelope()` and `DatasetService::prepareForShow()`
