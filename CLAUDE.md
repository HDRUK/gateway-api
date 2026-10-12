# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer run pest          # run all tests (parallel, bail on first failure)
composer run phpstan       # static analysis (level 3, app/ only)
composer run behat         # BDD integration tests (requires .env.behat)
composer run lint          # Laravel Pint PSR-12 formatter
composer run phpcs         # PSR-1 code style check
composer run generate-api-docs  # regenerate L5-Swagger OpenAPI docs
```

**Run a single test file or filter:**
```bash
php -d memory_limit=512M vendor/bin/pest tests/Feature/DatasetTest.php
php -d memory_limit=512M vendor/bin/pest --filter "test name"
```

**Local dev (preferred — requires Rancher Desktop + Helm + Tilt):**
```bash
tilt up
```
Tilt exposes the API on port 8000 and provides UI buttons to run Pest, migrate, or seed.

**Manual alternative:**
```bash
php artisan octane:frankenphp --host=0.0.0.0 --port=8100
php artisan migrate
```

## Pull requests

PRs use the repo template at `pull_request_template.md` (root). Fill in every section: **Describe your changes**, **Issue ticket link** (Jira `GAT-xxxx`), **Environment / Configuration changes**, **Requires migrations being run?**, and confirm tests pass. Complete the review checklist (self-review, unit tests + mocks, Behat for AC, Swagger for new endpoints, audit logs, `.env.example` / terraform for new env vars) — mark items N/A where they don't apply. Two approving reviews and a green pipeline are required to merge.

## Architecture

### Multi-version API with config-driven routing

Routes are **not** declared inline. Instead, `routes/api.v1.php`, `api.v2.php`, and `api.v3.php` each loop over their respective config file (`config/routes.php`, `config/routes_v2.php`, `config/routes_v3.php`) to register routes dynamically. Each route entry specifies `method`, `path`, `namespaceController`, `methodController`, `middleware`, and `constraint`. **To add or change a route, edit the relevant config file**, not the routes file.

The API is prefixed:
- `/api/v1/` → controllers in `app/Http/Controllers/Api/V1/` (68 controllers)
- `/api/v2/` → `Api/V2/` (17 controllers)
- `/api/v3/` → `Api/V3/` (5 controllers, dashboard-focused)

### Authentication

Two parallel auth systems coexist:
- **JWT** (`jwt.verify` middleware) — stateless, used by the main API. Handled by `JwtMiddleware` + `JwtController`. Applications (API clients) authenticate via `POST /auth` and receive a JWT.
- **Laravel Passport** (OAuth2) — used for SSO and the OpenID Connect flow. Controllers live in `app/Http/Controllers/SSO/`. Social login (Google, Azure, LinkedIn, OpenAthens) goes through `SocialLoginController`.

The `check.access` middleware enforces role/permission checks on top of authentication.

### Domain model

Core entities and their relationships:
- **User** belongs to one or more **Team**s (via TeamUser pivot with roles)
- **Dataset** has many **DatasetVersion**s; each version has structured metadata
- **DataAccessApplication** (DAR) belongs to a Dataset and a User; flows through sections and templates (`DataAccessTemplate`, `DataAccessSection`)
- **Collection** groups Datasets, Tools, Publications, and DURs (Data Use Registers)
- **Application** represents an API client (machine-to-machine auth)

### Services and cloud integrations

Services in `app/Services/` wrap external dependencies:
- `BigQueryService` — GCP BigQuery for analytics/dashboard data
- `CloudLoggerService` / `CloudPubSubService` — GCP logging and Pub/Sub messaging
- `GoogleSecretManagerService` — secret retrieval from GCP Secret Manager
- `ElasticClientControllerService` — Elasticsearch wrapper (used by search endpoints)
- `GatewayMetadataIngestionService` — ingests metadata from federated sources
- `AliasReplyScannerService` — scans email replies for DAR responses

### Testing setup

- **Pest** tests live in `tests/Feature/` and `tests/Unit/`. Feature tests use `CreatesApplication` trait and hit a real database.
- **Behat** tests use `.env.behat` (separate from `.env`) and orchestrate full user/team/dataset flows across ordered `.feature` files. Contexts are in `app/Behat/Context/`.
- PHPStan is set to level 3 — does not enforce strict type safety throughout.

### Response envelope

V1 endpoints return varied shapes. V2/V3 endpoints generally return `{ "message": "success", "data": ... }`. Use `Config::get('statuscodes.STATUS_OK.code')` etc. for HTTP status codes (defined in `config/statuscodes.php`).

### Dataset & Metadata Management

#### Data model

- **`datasets`** — stable identity: `pid` (UUID, immutable, public identifier), `status` (ACTIVE | DRAFT | ARCHIVED), `team_id`, `create_origin` (MANUAL | API | GMI), soft deletes. Use `pid` for external references; `id` is internal only.
- **`dataset_versions`** — append-only history: `version` (int, sequential from 1), `title`/`short_title` (regular columns — service must populate explicitly), `metadata` (JSON, null on delta rows), `patch` (JSON, null on snapshot rows), `gwdm_version` (varchar, default `'2.0'`). Index on `(dataset_id, version)`.

#### Versioning strategy: snapshots + RFC 6902 deltas

`DatasetService::SNAPSHOT_INTERVAL = 10`. Every structural update creates an **immutable new version row**:

- **Version 1 and every 10th version** → snapshot: `patch = null`, `metadata` = full GWDM envelope.
- **Versions 2–9, 11–19, …** → delta: `patch` = RFC 6902 array (via `swaggest/json-diff`), `metadata` = reduced (only `gwdmVersion` + `original_metadata`).

`DatasetService::reconstructGwdmMetadata()` replays deltas: find nearest snapshot ≤ target, extract GWDM via `handler->extractStoredGwdm()`, apply each delta in order. Throws `RuntimeException` if no snapshot exists (should never happen via the service layer).

**Legacy exception**: `updateV2()` overwrites the existing version row in place — no immutable history. Preserved for third-party integrations. V3 `update()` always creates a new row.

#### GWDM handler hierarchy

All version-specific logic lives in handlers (`app/Services/Gwdm/`), resolved by `GwdmHandlerFactory`. Do not call `version_compare()` outside the factory.

```
GwdmMetadataHandler (abstract)
├── Gwdm1xHandler     — GWDM < 1.1 (legacy publisher field, no 'version' in required)
├── Gwdm2xHandler     — GWDM ≥ 1.1, covers 2.0 + 2.1 (includes 'version' in required)
└── Gwdm30Handler     — GWDM 3.0, extends Gwdm2xHandler; SQL table persistence
```

Each handler implements:
- `prepareMetadata()` — apply version-specific mutations; calls `buildRequiredBlock()` and `buildPublisher()`
- `buildEnvelope()` — wrap into JSON storage format (`{gwdmVersion, metadata, original_metadata}`; Gwdm30Handler omits `metadata` key)
- `extractStoredGwdm()` — extract GWDM from storage; Gwdm30Handler returns `[]` (data is in SQL)
- `afterStore()` — post-create hook; no-op for JSON versions; Gwdm30Handler writes all SQL tables
- `afterRead()` — post-read hook; Gwdm30Handler reconstructs GWDM from SQL tables
- `extractLinkages()` — write junction tables; implemented on Gwdm2xHandler+

#### GWDM envelope structure

```json
{
  "gwdmVersion": "3.0",
  "metadata": { "required": {...}, "summary": {...}, "coverage": {...}, "provenance": {...},
                "accessibility": {...}, "linkage": { "datasetLinkage": {...} } },
  "original_metadata": { ... }
}
```

`original_metadata` is always preserved for audit. `required` is **reconstructed from the database** on every write — never preserved from input. GWDM 3.0 rows omit `metadata` from the envelope (data lives in SQL tables).

#### GWDM 3.0 SQL persistence

`Gwdm30Handler` decomposes all sections into dedicated SQL tables. Models in `app/Models/Gwdm30/`.

| Section | Model(s) | Cardinality |
|---------|----------|-------------|
| Accessibility | `Accessibility` | Single row |
| Summary | `Summary` | Single row |
| Coverage | `Coverage` | Single row |
| Provenance | `Provenance` | Single row |
| Observations | `Observation` | Multiple rows |
| Linkage | `LinkageMeta` | Single row (arrays stored as JSON) |
| Omics | `Omics` | Single row |
| Demographic frequency | `DemographicFrequency` | Multiple rows |
| Distributions | `Distribution` | Multiple rows |
| Quality annotations | `QualityAnnotation` | Multiple rows |
| Required | `Required` | Single row |
| Structural metadata | `StructuralTable` → `StructuralColumn` → `StructuralValue` | Hierarchical (FK chain) |
| Tissue sample collections | `TissueSampleCollection` | Multiple rows |
| Project grants | `ProjectGrant` | Multiple rows |
| Dataset filters | `DatasetFilter` | Multiple rows |
| ERD | `Erd` | Single row |

`persist()` runs inside `DB::transaction()`: delete-then-insert per section (idempotent). Migrations: `database/migrations/2026_06_23_000001` through `000016`. Backfill: `php artisan app:backfill-gwdm30 [--dry-run]` (targets `gwdm_version = '2.1'` rows only; run `php artisan migrate` first).

#### Metadata write flow (create / update)

1. **Translate** — `MMC::translateDataModelType()` converts input schema → GWDM via TRASER. Draft datasets skip strict validation.
2. **Prepare** — `handler->prepareMetadata()` rebuilds `required` block (with revisions) and `publisher`.
3. **Envelope** — `handler->buildEnvelope()` wraps GWDM + original metadata.
4. **Persist version** — `persistMetadataVersion()`: snapshot or delta based on `newVersionNumber % SNAPSHOT_INTERVAL`.
5. **SQL tables** — `handler->afterStore()` writes section tables (GWDM 3.0 only).
6. **Coverage** — `mapCoverage()` populates spatial coverage pivot.
7. **Jobs** — `dispatchJobs()` dispatches async work (see Ingestion Pipeline below).

#### Controllers

| Version | Class | Versioning behaviour |
|---------|-------|----------------------|
| V1 | `Api\V1\DatasetController` | Deprecated; uses `MetadataVersioning` trait; no patch support |
| V2 | `Api\V2\DatasetController` | Current write API; `update()` calls `updateV2()` (in-place legacy) |
| V3 | `Api\V3\DatasetController` | Immutable version history; read-focused; V3 `update()` creates new row |

**V3 endpoints**: `GET /api/v3/datasets/{id}/versions` (version index), `GET /api/v3/datasets/{id}/version/{version}` (reconstructed GWDM envelope).

#### Observers

`DatasetObserver` and `DatasetVersionObserver` handle all Elasticsearch indexing and job dispatch. **Raw DB writes bypassing Eloquent will not trigger indexing.** Always mutate via the model layer.

#### Ingestion pipeline (on ACTIVE status)

Jobs dispatched from `DatasetService::dispatchJobs()` (async):
- `LinkageExtraction` — parse `metadata.linkage.datasetLinkage` → populate `dataset_version_has_dataset_version`
- `TermExtraction` — NLP entity extraction (if `ted.enabled`) → `named_entities` pivot
- `ExtractToolsFromMetadata`, `ExtractPublicationsFromMetadata`, `ExtractProjectGrantsFromMetadata` — populate respective junction tables
- `IndexDataset` — full-text Elasticsearch index

#### Key invariants

- `patch = null` is the canonical snapshot flag — do not infer from any other column combination.
- `title` and `short_title` are regular columns (not DB GENERATED); the service layer must set them on every version write.
- `pid` (UUID) is the stable public ID; `id` (int) is internal only.
- `metadata` is nullable — delta rows carry no full GWDM; always handle null.
- Both `Dataset` and `DatasetVersion` use soft deletes — always account for `deleted_at`.
