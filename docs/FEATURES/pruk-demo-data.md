# PRUK demo data

`PrukDemoSeeder` seeds fictional PRUK longitudinal population studies (LPS)
for demonstrating and load-testing the combined search: keywords, variables
and "Refine further" conditions together.

```sh
php artisan db:seed --class=PrukDemoSeeder                          # 800 datasets
PRUK_DEMO_DATASETS=1000 php artisan db:seed --class=PrukDemoSeeder  # up to 2000

php artisan typesense:reindex datasets
php artisan typesense:reindex variables
```

The seeder doesn't index anything itself (see below), so always reindex
afterwards. The variables reindex embeds every variable and is the slow step:
expect around 30 variables per dataset.

## What you get

- **Cohorts**, each a parent study followed by its contact waves and the odd
  sub-study (biomarkers, genotyping, COVID-19 survey, linked records...).
  Waves link to their parent through `waveOf`. Eight archetypes: birth
  cohorts, life course studies, ageing studies, household panels, adolescent
  cohorts, women's health panels, twin registers and workforce cohorts.
- **A dozen fictional data custodians**, created as teams, spread across
  England, Scotland, Wales, Northern Ireland and UK-wide studies.
- **Variables** (`structuralMetadata`) on every dataset, drawn from 16 LPS
  domains (pregnancy and birth, mental health, cognition, biomarkers,
  socioeconomic circumstances...). Each domain has "core" variables always
  collected, so common searches reliably match. The panel's example search,
  "pregnancy outcomes" AND "depression measures", finds datasets. Wave
  variables carry a `wN_` prefix, as LPS data dictionaries usually do.
- **Search fields** that match the search UI's options exactly: wave type,
  recruitment status, follow-up frequency, access route and access time.
  Also data type and sub-type, keywords, population size, study dates, age
  range, biosamples, standards and geography. Every filter has something to
  find.

Every name is invented: no real study, custodian or person. Contact details
use `example.org`.

## How it works

- `Demo/PrukDemoCatalogue` holds the raw material: places, custodians,
  archetypes, sub-studies and the variable bank. Extend the data there.
- `Demo/PrukDemoGenerator` turns it into GWDM 2.3 envelopes. It's
  deterministic and prefix-stable: the same seed gives the same data, and
  `generate(n)` is the first n datasets of any larger run. It touches no
  database, so it's unit tested directly.
- `PrukDemoSeeder` writes them. It's idempotent on pid: rerunning updates
  the same datasets, and a larger run adds to a smaller one.
  - It saves with model events off, so the version observer's per-dataset
    jobs (variable sync, publication/tool/grant extraction) don't run and
    Scout doesn't sync each save to Typesense. One reindex does it all.
  - It records geography (`dataset_version_has_spatial_coverage`) by the same
    rule as `MetadataOnboard::mapCoverage()`. Without it the location filter
    would find nothing.
  - It runs `LinkageExtraction` for each dataset, as `PrukCrocodSampleSeeder`
    does, so `waveOf` becomes junction rows.

## Removing it

Every demo dataset belongs to one of the custodian teams in
`PrukDemoCatalogue::CUSTODIANS`, so they can be found (and soft-deleted) by
team. There's no removal command.

## Tests

- `tests/Unit/Database/PrukDemoGeneratorTest.php`: determinism, wave links,
  option values, age ranges, variables, and that every filter and the example
  search have something to find.
- `tests/Feature/PrukDemoSeederTest.php`: datasets, idempotency, growing a
  run, geography, and no observer jobs.
