## Screenshots (if relevant)

N/A — schema + model change only.

## Describe your changes

**PR 1 of 5** — base of the stack. Adds a queryable `gwdm_version` column to `dataset_versions` (the version previously lived only in the JSON envelope — unindexed, NULL on delta rows).

- **Migration** `…000001_add_gwdm_version…` — adds the indexed `varchar(20)` column, default `'2.0'`. Schema only.
- **Deployment step** `…_backfill_gwdm_version.php` — back-fills existing rows from `metadata->gwdmVersion`. Runs on deploy via `deploy:run` (after migrations); MySQL-only, no-op on SQLite.
- **`DatasetVersion`** — adds `gwdm_version` to `$fillable`; reduced-relation `selectRaw` reads the `title` column directly (not `JSON_EXTRACT`); `dataset()` select adds `team_id` (used by later PRs).

## Issue ticket link

GAT-9018

## Environment / Configuration changes (if applicable)

None.

## Requires migrations being run?

Yes — `2026_06_17_000001_add_gwdm_version_to_dataset_versions_table.php`, plus `php artisan deploy:run` to execute the `backfill_gwdm_version` deployment step (both run automatically on deploy via `docker/start.sh`).

## If not using the pre-push hook. Confirm tests pass:

- `pest tests/Feature/DatasetVersionGwdmVersionColumnTest.php` — 3 passed (column default `'2.0'`, mass-assignability, reduced-relation reads the `title` column for a delta row).
- `composer run phpstan` — clean (0 errors).

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [ ] I have created mocks for unit tests (where appropriate) — N/A
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A
- [ ] I have added Swagger annotations for new endpoints (if applicable) — no new endpoints
- [ ] I have added audit logs for new operation logic (if applicable) — N/A
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A
