## Screenshots (if relevant)

N/A — schema + models only.

## Describe your changes

**PR 3 of 5** — stacked on PR2. The GWDM 3.0 SQL persistence layer: **schema + Eloquent models only, no behaviour** (the `Gwdm30Handler` that reads/writes these tables lands in PR4).

- **~18 `gwdm30_*` tables** — single-row + multi-row sections, plus the `structural_tables → columns → values` chain.
- **18 Eloquent models** under `app/Models/Gwdm30/`.

### Reviewer notes
- **Migrations kept as the original fragmented sequence** (`create_*` + `add_missing_cols_*` + `add_dcat_fields_*`) rather than squashed — they have **already been applied in a shared environment** and cannot be safely reordered/squashed. New envs replay them in filename order.
- **M4 (removed)**: `2026_06_23_000009_backfill_gwdm30_distributions` was **deleted** — it ran at migrate time before `gwdm30_accessibility` is populated (permanent no-op), and its `access_service`→distribution fallback is unneeded: `Gwdm30Handler::persist()` already writes distributions from the metadata's own `distributions` section. The `gwdm30_distributions` **table** is unaffected (created by `…_000008_add_dcat_fields…`).

## Issue ticket link

GAT-9018

## Environment / Configuration changes (if applicable)

None.

## Requires migrations being run?

Yes — the `gwdm30_*` table migrations (`2026_06_17_000003`, `2026_06_23_0000xx`, `2026_06_26_000001`).

## If not using the pre-push hook. Confirm tests pass:

- `pest tests/Feature/Gwdm30SchemaTest.php` — 3 passed: all gwdm30 tables created by the migrations, the structural FK chain persists + traverses (with boolean/integer casts), and single-row sections cast JSON array columns.
- `composer run phpstan` — no new errors (the Gwdm30 models are clean; only the 5 pre-existing findings inherited from PR2 remain).

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [ ] I have created mocks for unit tests (where appropriate) — N/A
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A
- [ ] I have added Swagger annotations for new endpoints (if applicable) — no endpoints
- [ ] I have added audit logs for new operation logic (if applicable) — N/A
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A
