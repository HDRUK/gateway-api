# Dataset Model Relationships & Metadata Structure

## Model relationships

```mermaid
erDiagram
    datasets {
        int id PK
        string pid
        string status
        string create_origin
        int team_id FK
    }
    dataset_versions {
        int id PK
        int dataset_id FK
        int version
        json patch "null = snapshot / RFC 6902 array = delta"
        string title "materialised at write"
        string short_title "materialised at write"
    }
    metadata_envelope {
        string gwdmVersion "e.g. 2.0"
        object metadata "GWDM-translated (post-TRASER)"
        object original_metadata "raw caller input (pre-TRASER)"
    }
    metadata_gwdm {
        object summary "title, abstract, publisher, keywords (string)"
        object required "gatewayId, gatewayPid, issued, modified, version"
        object coverage "spatial, typicalAgeRange, pathway, followUp"
        object provenance "origin (source, purpose), temporal (startDate, endDate)"
        object accessibility "access, usage, formatAndStandards"
        array observations "observedNode, measuredValue, measurementUnit"
        object linkage "tools, dataUses, linkedDatasets, publications"
        array structuralMetadata "tables and columns definitions"
    }
    metadata_original {
        object summary "title, abstract, dataCustodian, keywords (array)"
        string version "e.g. 10.0.0"
        string identifier "dataset PID"
        object coverage "typicalAgeRangeMin + Max (not typicalAgeRange)"
        object provenance
        object accessibility
        array observations
        object linkage
    }
    teams {
        int id PK
        string pid
        string name
    }
    durs {
        int id PK
        string project_title
    }
    publications {
        int id PK
        string title
    }
    collections {
        int id PK
        string name
    }
    tools {
        int id PK
        string name
    }
    spatial_coverage {
        int id PK
        string region
    }

    datasets }o--|| teams : "team_id"
    datasets ||--o{ dataset_versions : "has versions"
    dataset_versions ||--|| metadata_envelope : "metadata column (JSON)"
    metadata_envelope ||--|| metadata_gwdm : "metadata"
    metadata_envelope ||--|| metadata_original : "original_metadata"
    dataset_versions }o--o{ durs : "dur_has_dataset_version"
    dataset_versions }o--o{ publications : "publication_has_dataset_version"
    dataset_versions }o--o{ collections : "collection_has_dataset_version"
    dataset_versions }o--o{ tools : "dataset_version_has_tool"
    dataset_versions }o--o{ spatial_coverage : "dataset_version_has_spatial_coverage"
```

## `dataset_versions.metadata` field structure

The `metadata` column is a single JSON blob with three top-level keys. `metadata.metadata` is the GWDM-normalised form produced by TRASER. `metadata.original_metadata` is the raw payload submitted by the caller, preserved exactly as received.

```mermaid
flowchart TD
    COL["dataset_versions.metadata\n─── JSON column ───"]

    COL --> GV["gwdmVersion\ne.g. '2.0'"]
    COL --> MM["metadata.metadata\n─── GWDM-translated (after TRASER) ───"]
    COL --> OM["metadata.original_metadata\n─── raw caller input (before TRASER) ───"]
    COL --> PATCH["patch column\nnull = snapshot\nRFC 6902 array = delta"]

    MM --> MM1["summary\n  title, abstract,\n  publisher.name/gatewayId,\n  keywords (semicolon-joined string),\n  contactPoint"]
    MM --> MM2["required\n  gatewayId, gatewayPid\n  issued, modified\n  version (e.g. '10.0.0')\n  revisions[]"]
    MM --> MM3["coverage\n  spatial, typicalAgeRange\n  pathway, followUp"]
    MM --> MM4["provenance / accessibility\n  observations / linkage\n  structuralMetadata / ..."]

    OM --> OM1["summary\n  title, abstract,\n  dataCustodian (≠ publisher)\n  keywords (array, not string)\n  contactPoint"]
    OM --> OM2["version, identifier\n  issued, modified\n  revisions[]"]
    OM --> OM3["coverage\n  typicalAgeRangeMin / Max\n  (≠ typicalAgeRange in GWDM)"]
    OM --> OM4["provenance / accessibility\n  observations / linkage\n  structuralMetadata / ..."]
```

**Key differences between the two sub-objects** (real example, dataset 619):

| Field | `metadata.metadata` (GWDM) | `metadata.original_metadata` (raw) |
|---|---|---|
| Publisher | `summary.publisher.name` + `gatewayId` | `summary.dataCustodian.name` + `identifier` |
| Keywords | semicolon-joined string | array of strings |
| Age range | `coverage.typicalAgeRange` = `"0-1"` | `coverage.typicalAgeRangeMin` = `0`, `typicalAgeRangeMax` = `1` |
| Spatial | string, semicolon-delimited | — |

## How versions surface in the API response

`GET /v1/datasets/{id}` (via `DatasetService::prepareForShow`) returns `data.versions[]` — one element per call (always the latest, or the requested version). The raw `dataset_versions` row is returned directly; the `metadata` JSON column is decoded in-place, so `versions[0].metadata` in the response **is** the three-key envelope above:

```
data
 ├── id, pid, status, create_origin          ← datasets row
 ├── team { … }                              ← teams join
 ├── durs_count / publications_count / …     ← aggregated from pivot tables
 ├── spatialCoverage [ … ]                   ← dataset_version_has_spatial_coverage
 ├── durs / publications / collections       ← via pivot → version_ids filter
 └── versions [
       {
         id, dataset_id, version             ← dataset_versions columns
         patch                               ← null (snapshot) or RFC 6902 array (delta)
         title, short_title                  ← materialized at write time
         metadata: {
           gwdmVersion                       ─┐
           metadata:          { … }           ├─ decoded from metadata JSON column
           original_metadata: { … }          ─┘
         }
         linked_dataset_versions [ … ]       ← self-referential pivot
       }
     ]
```

For delta versions (`patch != null`), `prepareForShow` calls `getReconstructedMetadataEnvelope()` to apply the RFC 6902 patches back to the nearest snapshot before returning — so the caller always receives a complete `metadata.metadata` object regardless of how it was stored.
