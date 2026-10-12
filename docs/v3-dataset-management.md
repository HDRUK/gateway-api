# V3 Dataset Management — System Diagrams

## 1. API Surface

Three endpoints make up the V3 dataset surface. `PUT` is the only mutating one; the two `GET` endpoints are read-only history accessors.

```mermaid
graph LR
    A[Client] -->|PUT /api/v3/datasets/:id| B[update]
    A -->|GET /api/v3/datasets/:id/versions| C[listVersions]
    A -->|GET /api/v3/datasets/:id/version/:version| D[showVersion]

    B --> E[Creates immutable version row\nRFC 6902 delta or full snapshot]
    C --> F[Returns lightweight index\nid · version · title · created_at]
    D --> G[Returns fully reconstructed\nGWDM envelope for that version]
```

---

## 2. PUT /api/v3/datasets/:id — Full Update Flow

```mermaid
flowchart TD
    REQ([PUT /api/v3/datasets/:id]) --> VALIDATE[UpdateDataset\nForm Request validation]
    VALIDATE --> AUTH[checkAccess\nteam ownership check]
    AUTH --> SVC[DatasetService::update]

    SVC --> EXTRACT[extractMetadata\nnormalise input shape]
    EXTRACT --> TRASER{MMC::translateDataModelType\nTRASER schema translation}

    TRASER -->|DRAFT dataset| TRASER_SKIP[Validation skipped\nallows incomplete metadata]
    TRASER -->|ACTIVE or other| TRASER_VALIDATE[Full GWDM validation]
    TRASER -->|Translation failed| ERR[throw RuntimeException\n→ HTTP 400]

    TRASER_SKIP --> REQUIRED
    TRASER_VALIDATE --> REQUIRED

    REQUIRED[buildRequiredBlock\nRebuild required section:\ngatewayId · gatewayPid\nissued · modified · revisions array]

    REQUIRED --> DATASET_UPDATE[dataset→update\nuser_id · team_id · status\ncreate_origin · updated]

    DATASET_UPDATE --> OBS_DATASET[[DatasetObserver::updated\nsee Observer Chain diagram]]

    DATASET_UPDATE --> PERSIST[persistMetadataVersion]

    PERSIST --> VERSION_NUM[newVersionNumber = currentVersion + 1]
    VERSION_NUM --> SNAPSHOT_CHECK{newVersionNumber\n% 10 === 0?}

    SNAPSHOT_CHECK -->|Yes — materialised snapshot| SNAP_WRITE[Write full envelope\npatch = NULL\nmetadata = full GWDM]
    SNAPSHOT_CHECK -->|No — delta row| RECONSTRUCT[reconstructGwdmMetadata\nreplay patches from nearest snapshot]
    RECONSTRUCT --> DIFF[computePatch\nswaggest/json-diff RFC 6902 diff]
    DIFF --> DELTA_WRITE[Write reduced envelope\npatch = RFC 6902 array\nmetadata = gwdmVersion + original_metadata only]

    SNAP_WRITE --> TITLES[extractTitleFields\ntitle · short_title from summary]
    DELTA_WRITE --> TITLES

    TITLES --> COVERAGE[mapCoverage\nUpdate spatial_coverage pivot table]
    COVERAGE --> OBS_VERSION[[DatasetVersionObserver::created\nsee Observer Chain diagram]]
    OBS_VERSION --> AUDIT[Auditor::log\naction=UPDATE · version number]
    AUDIT --> RESP([HTTP 200 OK])
```

---

## 3. Snapshot vs Delta Decision

```mermaid
flowchart LR
    subgraph Snapshot ["Snapshot row  (patch = NULL)"]
        S1[version 1\ncreate]
        S10[version 10]
        S20[version 20]
        S30[version 30]
    end

    subgraph Delta ["Delta rows  (patch = RFC 6902 array)"]
        D2[v2] --> D3[v3] --> D4[v4]
        D5[v5] --> D6[v6] --> D7[v7]
        D8[v8] --> D9[v9]
        D11[v11] --> D12[v12]
    end

    S1 -.->|basis for| D2
    S1 -.->|basis for| D9
    S10 -.->|materialised| D11
    S20 -.->|materialised| S30

    note1["Snapshots cap reconstruction cost\nat ≤ 9 delta applications"]
```

### What each row stores

| Field | Snapshot row | Delta row |
|---|---|---|
| `patch` | `NULL` | RFC 6902 array e.g. `[{op,path,value}]` |
| `metadata` | Full envelope: `{gwdmVersion, metadata:{...GWDM...}, original_metadata}` | Reduced: `{gwdmVersion, original_metadata}` — **no GWDM** |
| `title` | Extracted from `summary.title` | Extracted from `summary.title` |
| `short_title` | Extracted from `summary.shortTitle` | Extracted from `summary.shortTitle` |

---

## 4. GET /api/v3/datasets/:id/version/:version — Reconstruction Flow

```mermaid
flowchart TD
    REQ2([GET /api/v3/datasets/:id/version/:version]) --> EXISTS{DatasetVersion exists\nfor this dataset + version?}
    EXISTS -->|No| NOT_FOUND([HTTP 404])
    EXISTS -->|Yes| ENVELOPE[getReconstructedMetadataEnvelope]

    ENVELOPE --> FETCH_ROW[Fetch version row\nmetadata · patch columns]
    ENVELOPE --> RECONSTRUCT2[reconstructGwdmMetadata]

    RECONSTRUCT2 --> SNAP_QUERY[Find nearest snapshot\nversion ≤ target\nWHERE patch IS NULL\nORDER BY version DESC LIMIT 1]

    SNAP_QUERY -->|No snapshot found| RUNTIME_ERR[throw RuntimeException]
    SNAP_QUERY -->|Snapshot found| SNAP_IS_TARGET{snapshot.version\n=== targetVersion?}

    SNAP_IS_TARGET -->|Yes| RETURN_SNAP[Return snapshot GWDM directly\nzero patch applications]
    SNAP_IS_TARGET -->|No| FETCH_DELTAS[Fetch delta rows\nsnapshot.version < version ≤ target\nWHERE patch IS NOT NULL\nORDER BY version ASC]

    FETCH_DELTAS --> APPLY_LOOP[For each delta:\nJsonPatch::apply on stdClass graph]
    APPLY_LOOP --> RETURN_RECONSTRUCTED[Return reconstructed GWDM array]

    RETURN_SNAP --> BUILD_ENVELOPE[Build full envelope\ngwdmVersion · metadata · original_metadata]
    RETURN_RECONSTRUCTED --> BUILD_ENVELOPE

    BUILD_ENVELOPE --> RESP2([HTTP 200\nfull GWDM envelope])
```

---

## 5. Observer Chain — Side Effects After Every Write

```mermaid
flowchart TD
    subgraph DatasetObserver
        DA_UPD[updated event]
        DA_UPD -->|status ACTIVE → non-ACTIVE| DEINDEX[DeindexDataset job\nremove from Elasticsearch]
        DA_UPD -->|status non-ACTIVE → ACTIVE| REINDEX[ReindexDataset job\nrebuild Elasticsearch index]
        DA_UPD -->|first time ACTIVE| SET_ACTIVE_DATE[Set dataset.active_date = now]
    end

    subgraph DatasetVersionObserver
        DV_CREATED[created event]
        DV_CREATED -->|parent dataset ACTIVE| SET_VERSION_DATE[Set version.active_date = now]
        DV_CREATED -->|parent dataset ACTIVE| GRANTS[ExtractProjectGrantsFromMetadata job]
        DV_CREATED -->|parent dataset ACTIVE| PUBS[ExtractPublicationsFromMetadata job]
        DV_CREATED -->|parent dataset ACTIVE| TOOLS[ExtractToolsFromMetadata job]
        DV_CREATED -->|parent dataset ACTIVE| INDEX[IndexDataset job\nfull-text Elasticsearch index]
        DV_CREATED -->|parent dataset DRAFT/ARCHIVED| NOOP[No jobs dispatched]
    end

    subgraph AsyncJobs ["Async Jobs  (queued)"]
        GRANTS --> GRANTS_OUT[→ project_grant_has_dataset_version rows]
        PUBS --> PUBS_OUT[→ publication_has_dataset_version rows]
        TOOLS --> TOOLS_OUT[→ dataset_version_has_tool rows]
        INDEX --> ES[(Elasticsearch index)]
        REINDEX --> ES
        DEINDEX --> ES
    end
```

---

## 6. Full Data Flow — End to End

```mermaid
flowchart TD
    CLIENT([API Client]) -->|PUT /api/v3/datasets/:id\nwith metadata payload| CTRL[V3 DatasetController\n::update]

    CTRL --> SVC2[DatasetService\n::update]
    SVC2 -->|translate| TRASER2[TRASER / MMC\nGWDM schema translation]
    TRASER2 --> SVC2

    SVC2 -->|update| DS[(datasets table\nstatus · team_id · updated)]
    SVC2 -->|create| DV[(dataset_versions table\nversion · patch · metadata\ntitle · short_title)]
    SVC2 -->|upsert| SC[(dataset_version_has\n_spatial_coverage)]

    DS --> OBS1[DatasetObserver]
    DV --> OBS2[DatasetVersionObserver]

    OBS1 -->|status change| ES2[(Elasticsearch)]
    OBS2 -->|if ACTIVE| JOBS[Async Job Queue]

    JOBS --> ES2
    JOBS --> PIVOTS[(pivot tables\nproject_grants\npublications\ntools)]

    CLIENT2([API Client]) -->|GET /api/v3/datasets/:id/versions| CTRL2[V3 DatasetController\n::listVersions]
    CTRL2 -->|SELECT id,version,title,short_title,created_at| DV

    CLIENT3([API Client]) -->|GET /api/v3/datasets/:id/version/:v| CTRL3[V3 DatasetController\n::showVersion]
    CTRL3 --> RECON[Reconstruction engine\nfind snapshot → replay deltas]
    RECON -->|queries| DV
    RECON -->|full GWDM envelope| CLIENT3
```

---

## 7. GWDM Envelope Structure

```mermaid
graph TD
    ENV[metadata column\nJSON envelope]

    ENV --> GV[gwdmVersion\ne.g. 3.0]
    ENV --> META[metadata\nGWDM object — only present on snapshots]
    ENV --> ORIG[original_metadata\nalways present · pre-translation input]

    META --> REQ[required\ngatewayId · gatewayPid\nissued · modified · revisions]
    META --> SUM[summary\ntitle · shortTitle\ndescription · publisher]
    META --> DOC[documentation]
    META --> COV[coverage\nspatial string]
    META --> PROV[provenance]
    META --> ACC[accessibility]
    META --> LINK[linkage\ndatasetLinkage:\nlinkedDatasets\nisDerivedFrom\nisPartOf · isMemberOf]

    REQ --> REVISIONS["revisions array\n[{version: '1.0.0', url: '...'}, ...]"]
```

---

## Key Invariants

| Rule | Detail |
|---|---|
| `patch = NULL` identifies a snapshot | Do not infer from any other column |
| `metadata` is nullable on delta rows | Queries selecting `metadata` must handle `null` |
| `title` / `short_title` are regular columns | Service must populate them on every version write (not DB-generated) |
| `required` block is always rebuilt | Never trust user-supplied `required`; service overwrites it |
| `pid` (UUID) is the stable public ID | `id` (int) is internal only — external systems must use `pid` |
| Raw DB mutations bypass observers | All indexing/extraction will be silently skipped |
| Spatial coverage accumulates | Old pivot rows are not removed when a new version supersedes them |
