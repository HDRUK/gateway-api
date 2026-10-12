# GWDM 2.1 → 3.0: Implementation Change Record

> **Branch:** `feat/GAT-9018`  
> **Author:** Calum Macdonald  
> **Date:** 2026-06-23  
> **Purpose:** Reference document for downstream schema validation (schemata-2) and TRASER translation implementation.

---

## 1. Overview

GWDM 3.0 is a targeted evolution of GWDM 2.1. It introduces three layers of change:

1. **Persistence architecture** — every metadata field is now stored in dedicated, typed SQL columns rather than a compressed JSON blob.
2. **Schema additions** — four entirely new top-level sections and many new fields across existing sections.
3. **DCAT 3 / HealthDCAT-AP alignment** — key fields are typed, renamed, or augmented to map cleanly onto the W3C Data Catalog Vocabulary and the European health-sector DCAT Application Profile.

The schema itself is a strict superset of GWDM 2.1: every document that is valid GWDM 2.1 can be translated to a valid GWDM 3.0 document, and existing read/write infrastructure is fully backwards-compatible.

---

## 2. Persistence Architecture Change

### 2.1 GWDM 2.1 — JSON blob + RFC 6902 deltas

| Aspect | Detail |
|---|---|
| **Primary store** | `dataset_versions.metadata` (JSON column) — stores the full GWDM envelope on snapshots |
| **Delta compression** | `dataset_versions.patch` (text column) — RFC 6902 JSON Patch stored for version N when only fields changed relative to version N-1 |
| **Version discriminator** | Embedded inside JSON at `metadata.gwdmVersion` — unindexed, absent on delta rows |
| **Snapshot interval** | Every 10 versions a full snapshot is written; deltas are reconstructed by replaying patches |
| **Linkage extraction** | `LinkageExtraction` job reads `metadata.metadata.linkage`, writes to `dataset_version_has_dataset_version` and `publication_has_dataset_version` |

### 2.2 GWDM 3.0 — Fully decomposed SQL tables

| Aspect | Detail |
|---|---|
| **Primary store** | 10 dedicated SQL tables (one row per dataset version, except observations/distributions/qualityAnnotations/demographicFrequencies which are one-to-many) |
| **JSON blob** | Still written to `dataset_versions.metadata` as a read-cache; structured tables are the authoritative store |
| **RFC 6902 patches** | Retained for audit and 2.x compatibility; redundant for 3.0 rows |
| **Version discriminator** | `dataset_versions.gwdm_version` — a new dedicated indexed column (default `'2.0'`), back-filled from the JSON envelope on migration |
| **Linkage extraction** | Identical to 2.x; `Gwdm30Handler` inherits `extractLinkages()` from `Gwdm2xHandler` unchanged |

### 2.3 New SQL tables introduced

| Table | Cardinality | Purpose |
|---|---|---|
| `gwdm30_summary` | 1:1 with `dataset_versions` | Structured summary fields |
| `gwdm30_accessibility` | 1:1 with `dataset_versions` | Access, usage, and format fields |
| `gwdm30_coverage` | 1:1 with `dataset_versions` | Spatial, age, population fields |
| `gwdm30_provenance` | 1:1 with `dataset_versions` | Origin, temporal, retention fields |
| `gwdm30_observations` | 1:N with `dataset_versions` | Statistical observations |
| `gwdm30_linkage_meta` | 1:1 with `dataset_versions` | Non-relationship linkage metadata |
| `gwdm30_omics` | 1:1 with `dataset_versions` | Omics assay and platform |
| `gwdm30_demographic_frequencies` | 1:N with `dataset_versions` | Age/ethnicity/disease frequency histograms |
| `gwdm30_distributions` | 1:N with `dataset_versions` | `dcat:Distribution` records |
| `gwdm30_quality_annotations` | 1:N with `dataset_versions` | `dqv:QualityAnnotation` records (DUF scores, certifications) |

---

## 3. New `dataset_versions` Column

| Column | Type | Default | Purpose |
|---|---|---|---|
| `gwdm_version` | `varchar(20)` | `'2.0'` | Indexed discriminator for handler dispatch and schema-version queries |

This replaces reading `metadata->gwdmVersion` from the JSON, which was absent on delta rows and unindexable. All existing rows were back-filled with `COALESCE(JSON_EXTRACT(metadata, '$.gwdmVersion'), '2.0')`.

---

## 4. Schema Section Changes

### 4.1 `summary`

#### Fields retained from GWDM 2.1 (unchanged)

`abstract`, `contactPoint`, `keywords`, `controlledKeywords`, `datasetType`, `description`, `doiName`, `shortTitle`, `title`, `populationSize`, `publisher.name`, `publisher.gatewayId`

#### Fields changed

| Field | GWDM 2.1 | GWDM 3.0 | Notes |
|---|---|---|---|
| `publisher` | `{ name, gatewayId }` | `{ name, gatewayId, rorId }` | `rorId` added (9-char string) |

#### Fields added

| Field | Type | DCAT/Standard mapping | Description |
|---|---|---|---|
| `datasetSubType` | string | — | Sub-classification within `datasetType` |
| `inPipeline` | string | — | Pipeline processing status |
| `funders` | string | — | Funding body information |
| `licenseUrl` | URI string | `dct:license` | Dataset-level licence URI |
| `landingPage` | URI string | `dcat:landingPage` | Human-readable landing page |
| `creator` | object | `dct:creator` | Entity responsible for creating the dataset |
| `creator.name` | string | `foaf:name` | Creator display name |
| `creator.rorId` | string | ROR | Research Organisation Registry ID |
| `creator.orcidId` | string | ORCID | Researcher ORCID |
| `creator.gatewayId` | string | — | HDR UK Gateway internal ID |
| `theme` | string[] | `dcat:theme` | Array of controlled vocabulary URIs |

---

### 4.2 `coverage`

#### Fields retained from GWDM 2.1 (unchanged)

`spatial`, `pathway`, `followUp`

#### Fields changed

| Field | GWDM 2.1 | GWDM 3.0 | Reason |
|---|---|---|---|
| `typicalAgeRange` | `"0-150"` (free-text string) | **Removed** | Replaced by typed integer columns |
| — | — | `minTypicalAge` (integer) | `healthdcatap:minTypicalAge` — enables RDF serialisation |
| — | — | `maxTypicalAge` (integer) | `healthdcatap:maxTypicalAge` |

#### Fields added

| Field | Type | DCAT/Standard mapping | Description |
|---|---|---|---|
| `minTypicalAge` | integer | `healthdcatap:minTypicalAge` | Lower bound of typical participant age |
| `maxTypicalAge` | integer | `healthdcatap:maxTypicalAge` | Upper bound of typical participant age |
| `populationCoverage` | string | `healthdcatap:populationCoverage` | Description of population covered |
| `numberOfUniqueIndividuals` | integer | `healthdcatap:numberOfUniqueIndividuals` | Count of unique individuals in dataset |
| `numberOfRecords` | integer | `healthdcatap:numberOfRecords` | Total row/record count |
| `datasetCompleteness` | string | — | Completeness description or percentage |

---

### 4.3 `provenance`

#### Fields retained from GWDM 2.1 (unchanged)

`origin.purpose`, `origin.source`, `origin.collectionSituation`, `temporal.startDate`, `temporal.endDate`, `temporal.timeLag`, `temporal.accrualPeriodicity`

#### Fields added

| Field | Type | DCAT/Standard mapping | Description |
|---|---|---|---|
| `origin.imageContrast` | string | — | Image contrast for imaging datasets |
| `temporal.distributionReleaseDate` | date | — | Date of most recent public distribution release |
| `retentionPeriod` | object | `healthdcatap:retentionPeriod` | **New sub-section** |
| `retentionPeriod.startDate` | date | — | Start of data retention period |
| `retentionPeriod.endDate` | date | — | End of data retention period |

---

### 4.4 `accessibility`

#### Fields retained from GWDM 2.1 (unchanged)

`access.accessRights`, `access.accessService`, `access.accessRequestCost`, `access.deliveryLeadTime`, `access.jurisdiction`, `access.dataController`, `access.dataProcessor`, `formatAndStandards.vocabularyEncodingSchemes`, `formatAndStandards.conformsTo`, `formatAndStandards.languages`

#### Fields changed (type/name corrections)

| Field | GWDM 2.1 | GWDM 3.0 | Reason |
|---|---|---|---|
| `usage.dataUseLimitation` | `string` (e.g. `"General research use"`) | `string[]` (array) | SQL column is `JSON`; enables multi-value terms |
| `usage.dataUseRequirement` | `string` (singular key) | `dataUseRequirements` — `string[]` (plural array) | Key rename + type correction |
| `formatAndStandards.formats` | `"CSV;,;JSON"` (HDRUK semicolon-delimited string) | `string[]` (e.g. `["CSV", "JSON"]`) | SQL column is `JSON`; semicolon format was HDRUK-internal only |

#### Fields added

| Field | Type | DCAT/Standard mapping | Description |
|---|---|---|---|
| `access.accessServiceCategory` | string | — | Category classification for the access service |
| `access.legalBasis` | string | `dpv:hasLegalBasis` | Legal basis for processing (DPV vocabulary) |
| `access.personalData` | string | `dpv:hasPersonalData` | Type of personal data held |
| `access.applicableLegislation` | string | `dcatap:applicableLegislation` | Applicable legislation (e.g. GDPR, UK GDPR) |

---

### 4.5 `observations` (unchanged structure, now SQL-backed)

No schema changes from GWDM 2.1. All five fields are now persisted to `gwdm30_observations` (one row per observation) rather than the JSON blob.

| Field | Type | Description |
|---|---|---|
| `observedNode` | string | Entity being observed (required) |
| `measuredValue` | decimal | Numeric measurement |
| `observationDate` | date | Date of observation |
| `measuredProperty` | string | Property being measured |
| `disambiguatingDescription` | string | Free-text description |

---

### 4.6 `linkage` (extended with flat metadata fields)

#### Fields retained from GWDM 2.1 (unchanged)

`datasetLinkage` (sub-keyed by type: `isDerivedFrom`, `isPartOf`, `isMemberOf`, `linkedDatasets`), `publicationAboutDataset`, `publicationUsingDataset`

Linkage extraction continues to write to `dataset_version_has_dataset_version` and `publication_has_dataset_version` exactly as in GWDM 2.1.

#### Fields added (stored in `gwdm30_linkage_meta`)

| Field | Type | Description |
|---|---|---|
| `isGeneratedUsing` | string | Tools/processes used to generate the dataset |
| `associatedMedia` | string | URLs to associated media resources |
| `dataUses` | string | Data use register references |
| `isReferenceIn` | string | Publications/resources this dataset is referenced in |
| `tools` | string | Tools that use or process this dataset |
| `investigations` | string | Investigations this dataset contributes to |
| `syntheticDataWebLink` | string | URL to synthetic version of this dataset |

---

## 5. New Top-Level Sections

### 5.1 `omics` (new)

Stored in `gwdm30_omics` (1:1).

| Field | Type | Description |
|---|---|---|
| `assay` | string | Assay type (e.g. `WGS`, `RNA-seq`) |
| `platform` | string | Sequencing/measurement platform |

---

### 5.2 `demographicFrequency` (new)

Stored in `gwdm30_demographic_frequencies` (1:N, one row per bin). Three sub-categories.

#### `demographicFrequency.age[]`

| Field | Type | Description |
|---|---|---|
| `bin` | string | Age band label (e.g. `"0-4"`) |
| `count` | integer | Count of individuals in this band |

#### `demographicFrequency.ethnicity[]`

| Field | Type | Description |
|---|---|---|
| `bin` | string | Ethnicity group label |
| `count` | integer | Count of individuals in this group |

#### `demographicFrequency.disease[]`

| Field | Type | Description |
|---|---|---|
| `diseaseCode` | string | Coded disease identifier |
| `diseaseCodeVocabulary` | string | Coding vocabulary (e.g. `ICD-10`, `SNOMED CT`) |
| `count` | integer | Count of individuals with this code |

---

### 5.3 `distributions[]` (new — DCAT alignment)

Stored in `gwdm30_distributions` (1:N). Maps directly onto `dcat:Distribution`. A dataset version may have zero or more distributions representing different physical or accessible forms of the dataset.

| Field | Type | DCAT mapping | Description |
|---|---|---|---|
| `title` | string | `dct:title` | Distribution display name |
| `description` | string | `dct:description` | Human-readable description |
| `accessUrl` | URI | `dcat:accessURL` | URL to access the distribution |
| `downloadUrl` | URI | `dcat:downloadURL` | Direct download URL |
| `mediaType` | string | `dcat:mediaType` | IANA media type (e.g. `text/csv`) |
| `format` | string | `dct:format` | File format label |
| `byteSize` | integer | `dcat:byteSize` | Size in bytes |
| `licenseUrl` | URI | `dct:license` | Distribution-level licence (overrides dataset licence) |
| `accessService` | string | `dcat:accessService` | Service providing access to this distribution |
| `issued` | date | `dct:issued` | Date distribution was published |
| `modified` | date | `dct:modified` | Date distribution was last modified |

> **Migration note:** Existing `accessibility.access.accessService` values are back-filled into a `gwdm30_distributions` row (`access_url` only) so that pre-3.0 access service links are immediately representable as `dcat:Distribution` records without requiring a re-ingest.

---

### 5.4 `qualityAnnotations[]` (new — DQV alignment)

Stored in `gwdm30_quality_annotations` (1:N). Maps onto `dqv:QualityAnnotation` and supports two annotation types: Data Utility Framework (DUF) dimension scores and external certifications.

| Field | Type | Standard mapping | Description |
|---|---|---|---|
| `annotationType` | enum: `duf_score` \| `certification` | `dqv:inDimension` classifier | Type of quality evidence |
| `qualityDimension` | string | `dqv:inDimension` | Quality dimension (e.g. `completeness`, `accuracy`) |
| `qualityValue` | string | `dqv:value` | Score or grade value |
| `qualityDescription` | string | `skos:note` | Human-readable description of the annotation |
| `certificationUrl` | URI | — | Link to certification document |
| `annotationDate` | date | `prov:generatedAtTime` | Date annotation was recorded |

---

## 6. DCAT / HealthDCAT-AP Alignment Summary

The following table shows all new or changed fields that align to an external standard.

| GWDM 3.0 Field | Standard | Property URI | Notes |
|---|---|---|---|
| `summary.licenseUrl` | DCAT 3 | `dct:license` | Dataset-level licence URI |
| `summary.landingPage` | DCAT 3 | `dcat:landingPage` | Human-readable portal page |
| `summary.creator` | DCAT 3 | `dct:creator` | Object with name/rorId/orcidId/gatewayId |
| `summary.theme` | DCAT 3 | `dcat:theme` | Array of controlled vocabulary URIs |
| `coverage.minTypicalAge` | HealthDCAT-AP | `healthdcatap:minTypicalAge` | Replaces free-text `typicalAgeRange` |
| `coverage.maxTypicalAge` | HealthDCAT-AP | `healthdcatap:maxTypicalAge` | Replaces free-text `typicalAgeRange` |
| `coverage.populationCoverage` | HealthDCAT-AP | `healthdcatap:populationCoverage` | — |
| `coverage.numberOfUniqueIndividuals` | HealthDCAT-AP | `healthdcatap:numberOfUniqueIndividuals` | — |
| `coverage.numberOfRecords` | HealthDCAT-AP | `healthdcatap:numberOfRecords` | — |
| `provenance.retentionPeriod` | HealthDCAT-AP | `healthdcatap:retentionPeriod` | New sub-section |
| `accessibility.access.legalBasis` | DPV | `dpv:hasLegalBasis` | GDPR legal basis |
| `accessibility.access.personalData` | DPV | `dpv:hasPersonalData` | Personal data category |
| `accessibility.access.applicableLegislation` | DCAT-AP 3 | `dcatap:applicableLegislation` | Applicable legislation |
| `distributions[].accessUrl` | DCAT 3 | `dcat:accessURL` | Distribution access endpoint |
| `distributions[].downloadUrl` | DCAT 3 | `dcat:downloadURL` | Distribution download endpoint |
| `distributions[].mediaType` | DCAT 3 | `dcat:mediaType` | IANA media type |
| `distributions[].byteSize` | DCAT 3 | `dcat:byteSize` | — |
| `distributions[].accessService` | DCAT 3 | `dcat:accessService` | — |
| `qualityAnnotations[].qualityDimension` | DQV | `dqv:inDimension` | — |
| `qualityAnnotations[].qualityValue` | DQV | `dqv:value` | — |
| `qualityAnnotations[].qualityDescription` | SKOS | `skos:note` | — |
| `qualityAnnotations[].annotationDate` | PROV-O | `prov:generatedAtTime` | — |

---

## 7. Handler and Dispatch Changes

### 7.1 Handler hierarchy

```
GwdmMetadataHandler (abstract)
├── Gwdm1xHandler    — GWDM < 1.1 (legacy publisher format)
├── Gwdm2xHandler    — GWDM 1.1–2.x (JSON blob, RFC 6902)
│   ├── Gwdm20Handler
│   ├── Gwdm21Handler
│   └── Gwdm30Handler  ← NEW
```

`Gwdm30Handler` extends `Gwdm2xHandler` because:
- `prepareMetadata()`, `buildRequiredBlock()`, `buildPublisher()` are identical to 2.x
- `extractLinkages()` is inherited unchanged
- Only `afterStore()` and `afterRead()` are overridden to add SQL table persistence

### 7.2 `GwdmHandlerFactory` change

| Before | After |
|---|---|
| `SUPPORTED_VERSIONS = ['2.0', '2.1']` | `SUPPORTED_VERSIONS = ['2.0', '2.1', '3.0']` |
| No `3.0` match arm | `$version === '3.0' => new Gwdm30Handler($version)` |

### 7.3 New lifecycle hooks in `GwdmMetadataHandler`

| Hook | 2.x behaviour | 3.0 behaviour |
|---|---|---|
| `afterStore(Dataset, DatasetVersion, array $gwdm)` | No-op | Writes all 10 SQL tables inside a DB transaction |
| `afterRead(DatasetVersion)` | Reads linkages from SQL junction tables and overlays onto JSON | Reads all 10 SQL tables; reconstructs full GWDM section array from SQL |

---

## 8. Backfill Command

A new Artisan command (`app:backfill-gwdm30`) translates existing GWDM 2.1 dataset versions to 3.0 and persists them to the new SQL tables.

```bash
# Preview without writing
php artisan app:backfill-gwdm30 --dry-run

# Execute migration
php artisan app:backfill-gwdm30
```

The command processes GWDM 2.1 rows in chunks of 50, updating `gwdm_version` to `'3.0'` and writing all structured table rows.

---

## 9. GWDM 3.0 Document Shape (JSON)

Below is the full top-level structure of a GWDM 3.0 document, with all sections annotated:

```json
{
  "gwdmVersion": "3.0",
  "metadata": {
    "required": {
      "gatewayId": "...",
      "gatewayPid": "...",
      "issued": "...",
      "modified": "...",
      "version": "1.0.0",
      "revisions": [...]
    },
    "summary": {
      "title": "...", "shortTitle": "...",
      "abstract": "...", "description": "...",
      "contactPoint": "...", "keywords": "...",
      "controlledKeywords": [...], "datasetType": "...",
      "datasetSubType": "...",           // NEW
      "inPipeline": "...",               // NEW
      "funders": "...",                  // NEW
      "doiName": "...",
      "licenseUrl": "...",               // NEW (dct:license)
      "landingPage": "...",              // NEW (dcat:landingPage)
      "creator": {                       // NEW (dct:creator)
        "name": "...", "rorId": "...", "orcidId": "...", "gatewayId": "..."
      },
      "theme": [...],                    // NEW (dcat:theme)
      "publisher": { "name": "...", "gatewayId": "...", "rorId": "..." },
      "populationSize": 0
    },
    "coverage": {
      "spatial": "...",
      "minTypicalAge": 0,                // NEW (was typicalAgeRange string)
      "maxTypicalAge": 150,              // NEW (was typicalAgeRange string)
      "populationCoverage": "...",       // NEW (healthdcatap)
      "numberOfUniqueIndividuals": 0,   // NEW (healthdcatap)
      "numberOfRecords": 0,             // NEW (healthdcatap)
      "pathway": "...", "followUp": "...",
      "datasetCompleteness": "..."       // NEW
    },
    "provenance": {
      "origin": {
        "purpose": "...", "source": "...", "collectionSituation": "...",
        "imageContrast": "..."            // NEW
      },
      "temporal": {
        "startDate": "...", "endDate": "...",
        "timeLag": "...", "accrualPeriodicity": "...",
        "distributionReleaseDate": "..."  // NEW
      },
      "retentionPeriod": {               // NEW (healthdcatap)
        "startDate": "...", "endDate": "..."
      }
    },
    "accessibility": {
      "access": {
        "accessRights": "...", "accessService": "...",
        "accessServiceCategory": "...",  // NEW
        "accessRequestCost": "...", "deliveryLeadTime": "...",
        "jurisdiction": "...", "dataController": "...", "dataProcessor": "...",
        "legalBasis": "...",             // NEW (dpv:hasLegalBasis)
        "personalData": "...",           // NEW (dpv:hasPersonalData)
        "applicableLegislation": "..."   // NEW (dcatap:applicableLegislation)
      },
      "usage": {
        "dataUseLimitation": [...],      // CHANGED: was string, now string[]
        "dataUseRequirements": [...],    // CHANGED: was dataUseRequirement (singular string)
        "resourceCreator": { "name": "..." }
      },
      "formatAndStandards": {
        "vocabularyEncodingSchemes": "...", "conformsTo": "...", "languages": "...",
        "formats": [...]                 // CHANGED: was semicolon string, now string[]
      }
    },
    "linkage": {
      "datasetLinkage": { "isDerivedFrom": [...], "isPartOf": [...], "isMemberOf": [...], "linkedDatasets": [...] },
      "publicationAboutDataset": [...],
      "publicationUsingDataset": [...],
      "isGeneratedUsing": "...",         // NEW
      "associatedMedia": "...",          // NEW
      "dataUses": "...",                 // NEW
      "isReferenceIn": "...",            // NEW
      "tools": "...",                    // NEW
      "investigations": "...",           // NEW
      "syntheticDataWebLink": "..."      // NEW
    },
    "observations": [
      { "observedNode": "...", "measuredValue": 0, "observationDate": "...", "measuredProperty": "...", "disambiguatingDescription": "..." }
    ],
    "omics": {                           // NEW TOP-LEVEL SECTION
      "assay": "...", "platform": "..."
    },
    "demographicFrequency": {            // NEW TOP-LEVEL SECTION
      "age": [{ "bin": "0-4", "count": 0 }],
      "ethnicity": [{ "bin": "White British", "count": 0 }],
      "disease": [{ "diseaseCode": "E11", "diseaseCodeVocabulary": "ICD-10", "count": 0 }]
    },
    "distributions": [                   // NEW TOP-LEVEL SECTION (dcat:Distribution)
      {
        "title": "...", "description": "...",
        "accessUrl": "...", "downloadUrl": "...",
        "mediaType": "text/csv", "format": "CSV",
        "byteSize": 0, "licenseUrl": "...", "accessService": "...",
        "issued": "...", "modified": "..."
      }
    ],
    "qualityAnnotations": [              // NEW TOP-LEVEL SECTION (dqv:QualityAnnotation)
      {
        "annotationType": "duf_score",
        "qualityDimension": "completeness", "qualityValue": "3",
        "qualityDescription": "...", "certificationUrl": "...", "annotationDate": "..."
      }
    ]
  }
}
```

---

## 10. Actions Required in Downstream Services

### `schemata-2` (schema validation)

| Action | Detail |
|---|---|
| Create `GWDM/3.0/schema.json` | Full JSON Schema document covering all 3.0 fields listed in this document |
| Register `3.0` in schema registry | So TRASER can route validation for `gwdmVersion: "3.0"` requests |
| Key type changes to enforce | `coverage.minTypicalAge` / `maxTypicalAge` must be `integer`; `coverage.typicalAgeRange` must not appear; `accessibility.usage.dataUseLimitation` must be `array`; `accessibility.usage.dataUseRequirements` (plural) must be `array`; `accessibility.formatAndStandards.formats` must be `array` |

### `traser` (translation)

| Action | Detail |
|---|---|
| GWDM 2.1 → GWDM 3.0 mapping | Translate `typicalAgeRange: "0-150"` to `minTypicalAge: 0, maxTypicalAge: 150`; rename `dataUseRequirement` → `dataUseRequirements` and wrap in array; convert `formats` semicolon string to array; convert `dataUseLimitation` string to array |
| GWDM 3.0 → GWDM 2.1 mapping (read) | Inverse transforms for any consumer requesting GWDM 2.1 output schema |
| Register `3.0` as a valid target version | So `x-gwdm-version: 3.0` header routes correctly |
