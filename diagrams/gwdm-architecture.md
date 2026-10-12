# GWDM Metadata Management — Architecture Overview

This diagram shows the top-level architecture of the Gateway 2.0 GWDM metadata management system across three tiers: partner frontends, the Laravel API (including the TRASER translation sidecar), and MySQL storage with its two persistence strategies.

Four partner frontends send metadata writes carrying `x-partner-context` and `x-gwdm-version` headers. The API's Metadata Management layer forwards the raw payload to TRASER for schema translation, receives a negotiated GWDM envelope back, and then persists it to MySQL. Versions 2.0 and 2.1 are stored as JSON envelopes (full snapshots every 10 versions with RFC 6902 delta patches in between); version 3.0 is decomposed into dedicated SQL tables.

![GWDM Architecture](./png/gwdm-architecture.png)

## Source (Mermaid)

```mermaid
flowchart TD
    %% ── Tier 1: Partner Frontends ────────────────────────────────────────────
    GW_HDRUK(["gateway-web\n[HDRUK]"])
    GW_CRUK(["gateway-web\n[CRUK]"])
    GW_PRUK(["Custom FE\n[PRUK / others]"])
    EXT(["External\nAPI client"])

    %% ── Tier 2: Gateway API ──────────────────────────────────────────────────
    subgraph API["Gateway API (Laravel)"]
        direction TB
        AUTH["Auth / SSO"]
        SEARCH["Search\n(Elasticsearch)"]
        META["Metadata Management"]
    end

    %% ── TRASER (external service) ────────────────────────────────────────────
    TRASER["TRASER\n─────────────────────\nschema repo + partner mapping files\nHDRUK 4.0 · CRUK 1.1 · PRUK…"]

    %% ── Tier 3: Storage ──────────────────────────────────────────────────────
    subgraph STORAGE["Storage (MySQL)"]
        direction TB
        DS["datasets\n─────────────────────\npid · team_id · partner_context · status"]
        DV["dataset_versions\n─────────────────────\ngwdm_version · metadata (snapshot)\npatch (RFC 6902 delta)\n─────────────────────\nsnapshot every 10 versions;\ndeltas in between"]
    end

    %% ── Storage strategy fork ────────────────────────────────────────────────
    JSON_ENV["JSON envelope"]
    SQL_TABLES["Decomposed SQL tables"]

    %% ── Edges: Tier 1 → API ──────────────────────────────────────────────────
    GW_HDRUK -->|"x-partner-context: HDRUK\nx-gwdm-version: 2.0 | 2.1 | 3.0"| META
    GW_CRUK  -->|"x-partner-context: CRUK\nx-gwdm-version: 2.0 | 2.1 | 3.0"| META
    GW_PRUK  -->|"x-partner-context: PRUK\nx-gwdm-version: 2.0 | 2.1 | 3.0"| META
    EXT      -->|"x-partner-context: HDRUK | CRUK | PRUK\nx-gwdm-version: 2.0 | 2.1 | 3.0"| META

    %% ── Edges: META ↔ TRASER ─────────────────────────────────────────────────
    META -->|"translate schema"| TRASER
    TRASER -->|"GWDM (version negotiated)"| META

    %% ── Edges: META → Storage ────────────────────────────────────────────────
    META --> DS
    DS --> DV

    %% ── Edges: DV → Storage strategies ──────────────────────────────────────
    DV -->|"GWDM 2.0 / 2.1"| JSON_ENV
    DV -->|"GWDM 3.0"| SQL_TABLES

    %% ── Class definitions ────────────────────────────────────────────────────
    classDef http     fill:#475da7,stroke:#354681,color:#fff
    classDef process  fill:#ffffff,stroke:#29235c,color:#29235c
    classDef mw       fill:#3db28c,stroke:#2a8c6e,color:#fff
    classDef decision fill:#475da7,stroke:#354681,color:#fff
    classDef ok       fill:#3db28c,stroke:#2a8c6e,color:#fff
    classDef err      fill:#f98e2b,stroke:#c96f1a,color:#111
    classDef stub     fill:#6c7cc0,stroke:#475da7,color:#fff
    classDef storage  fill:#addad9,stroke:#29235c,color:#29235c
    classDef strategy fill:#d0d3d4,stroke:#29235c,color:#29235c

    class GW_HDRUK,GW_CRUK,GW_PRUK,EXT http
    class AUTH,SEARCH,META process
    class TRASER mw
    class DS,DV storage
    class JSON_ENV,SQL_TABLES strategy
```
