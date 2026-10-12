## Screenshots (if relevant)

N/A.

## Describe your changes

**PR 5 of 5** — stacked on PR4. Final PR.

Adds `app:backfill-gwdm30` — migrates ACTIVE GWDM 2.x datasets to 3.0 (reconstruct latest 2.x → translate via TRASER → persist a new 3.0 `dataset_version` row). Flags: `--dry-run`, `--force` (re-persist existing 3.0 rows in place), `--dataset-id`, `--min/--max-dataset-id`, with resumable logging.

**Run manually** — heavy, platform-wide, hard to reverse. Dry-run first, inspect, then run for real.

### Reviewer notes
- This is a **manual** command by design — no deployment step auto-runs it. The gwdm30 distributions back-fill migration was **removed in PR3** as unneeded (`persist()` writes distributions from metadata); this command does not re-introduce it.
- **`.github/workflows/deployment.yaml` is intentionally NOT part of this PR.** That change (a cloud-db-backup step) is GAT-9140 (#1696), already merged to `dev`; it only appeared in the original branch diff because local `dev` was stale.
- **Safe ordering:** the C1 reconstruction fix lands in PR2, so running this backfill cannot make a 3.0 row the latest version and strip search titles/abstracts.

## Issue ticket link

GAT-9018

## Environment / Configuration changes (if applicable)

None (new artisan command only).

## Requires migrations being run?

No (relies on the tables from PR3 already being present).

## If not using the pre-push hook. Confirm tests pass:

- `pest tests/Feature/BackfillGwdm30CommandTest.php` — 3 passed: `--dry-run` creates no 3.0 version; a dataset already at 3.0 is skipped (idempotency); an empty id-range is a clean no-op. (The translate→persist path itself is covered by `DatasetVersionGwdm30Test` in PR4; a live 2.x→3.0 TRASER translation is not reproducible through the shared test mock.)

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [x] I have added appropriate unit tests
- [x] I have created mocks for unit tests (where appropriate)
- [ ] I have added appropriate Behat tests to confirm AC (if applicable) — N/A
- [ ] I have added Swagger annotations for new endpoints (if applicable) — no endpoints (CLI command)
- [ ] I have added audit logs for new operation logic (if applicable) — command writes a per-run log file
- [ ] I have added new environment variables to the .env.example file (if applicable) — N/A
- [ ] I have added new environment variables to terraform repository (if applicable) — N/A
