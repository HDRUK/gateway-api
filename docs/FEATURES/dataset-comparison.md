# Dataset comparison

`POST /api/v2/datasets/compare` puts 2 to 4 datasets' metadata side by side.
The first is the **reference**: for every field, each other dataset gets a call
against it: **identical**, **similar**, **different**, or **missing** (either
has no value). It is the API behind the
"Study metadata" tab of the PRUK comparison page.

Variables are compared separately, by concept, with `POST /api/v2/search/variables`
(`dataset_ids` plus `terms`). The two are independent; this endpoint leaves
`structuralMetadata` out of its response.

```sh
curl -X POST localhost:8000/api/v2/datasets/compare \
  -H 'Content-Type: application/json' -H 'x-partner-context: PRUK' \
  -d '{"dataset_ids": [11, 12, 34]}'
```

## Request

| Field | Rules |
| --- | --- |
| `dataset_ids` | 2 to `comparison.max_datasets` distinct positive integers. The first is the reference. |

**The limit is configurable**: `COMPARISON_MAX_DATASETS` (default 4). The
client has its own `NEXT_PUBLIC_COMPARE_MAX_DATASETS`, which caps how many
datasets can be selected; keep the two the same.

Datasets must be **active** and visible to the caller's partner
(`x-partner-context`), as for any dataset read. Otherwise the response is 404
and names the ids. Validation failures are 400 `INVALID_ARGUMENT`, as across
this API. Each dataset's **latest version** is compared.

## Response

```jsonc
{
  "message": "success",
  "data": {
    "datasets": [            // in the order asked for
      { "id": 11, "title": "…", "publisherName": "…", "gwdmVersion": "2.3", "metadata": { /* GWDM, no structuralMetadata */ } },
      { "id": 12, … },
      { "id": 34, … }
    ],
    "fields": {              // keyed by dotted path
      "coverage.typicalAgeRange": {
        "type": "range",
        "values": ["50-99", "50-75", "0-35"],   // null for a dataset that doesn't record it
        "calls": [                              // each dataset against the reference
          null,
          { "outcome": "similar", "score": 0.510204 },
          { "outcome": "different", "score": 0 }
        ]
      }
    },
    "thresholds": { "text": 0.5, "list": 0.5, "range": 0.5, "count": 0.8, "exact": 1.0 }
  }
}
```

- `fields` come in the order the datasets first record them. A field no
  dataset records is not listed.
- Every call is against the reference, never between the other datasets. To
  compare against a different dataset, put it first.
- `score` is null for `missing`: a field one dataset doesn't record says
  nothing about how alike the two are.
- `thresholds` are returned so a client can explain a call.
- A list of objects (`tissuesSampleCollection[].materialType`) is one list
  field per key, `tissuesSampleCollection.materialType`.
- Not compared: `required` (ids and revisions differ for every pair),
  `structuralMetadata` (its own comparison) and `linkage` (points at other
  datasets).

## How a field is scored

Each field has a type, and each type one method. Scores run from 0 (nothing in
common) to 1 (the same), rounded to six places. A score of 1 is **identical**,
one at or above the type's boundary in `config/comparison.php` is **similar**,
anything lower is **different**.

| Type | Example | Method | Notes |
| --- | --- | --- | --- |
| `text` | abstract | cosine similarity of word counts | Case, punctuation and stop words ignored. |
| `list` | keywords, standards | Jaccard | Order, case and duplicates ignored; `;,;`-delimited strings or arrays. Items match exactly: `E11.9` and `E11` are different codes. |
| `range` | age range | overlap over combined span | `"20-55"` or `"20–55"`. A single point (`0-0`) equals itself. |
| `count` | population size | smaller over larger | `"8,500"` reads as 8500. |
| `exact` | contact, URL, dates, yes/no, access route | exact match | Case-insensitive, trimmed. No similar band. |

A value that can't be read as its type (a "range" of `adults`) is `missing`,
not guessed.

**Types are read from the values**, not a fixed field list, so another GWDM
version or a partner's extra fields compare too: booleans and short strings
are `exact`, numbers `count`, arrays and delimited strings `list`, `12-25`
`range`, and six words or more `text`. Where the two datasets disagree the
wider type wins, so `DDI` against `DDI;,;Local` is a list, not a mismatch.
Short titles therefore compare as names (`exact`), not prose.

**Stop words** are the search's own, `TypesenseService::DEFAULT_STOPWORDS`.

## Code

- `app/Services/Comparison/FieldComparator.php`: one method per type.
- `app/Services/Comparison/DatasetComparison.php`: flattens two `metadata`
  blocks (`Arr::dot`), types each field and compares it.
- `app/Http/Controllers/Api/V2/DatasetComparisonController.php`
- `config/comparison.php`: boundaries and dataset count.

## Tests

- `tests/Unit/Services/Comparison/FieldComparatorTest.php`: each method and
  its edge cases (missing, zero, formats, boundaries).
- `tests/Unit/Services/Comparison/DatasetComparisonTest.php`: flattening,
  typing, missing and one-sided fields, summary maths.
- `tests/Feature/DatasetComparisonTest.php`: the endpoint, ordering, latest
  version, validation, and active/partner scoping.

## Findings from the spike

Run against the PRUK demo data (`docs/FEATURES/pruk-demo-data.md`).

- **The scores separate the cases.** Averaging the scored fields, a study
  against its own wave came out at about 0.80 and two unrelated birth cohorts
  at about 0.35. (The API returns no overall score: the plan asks only for a
  call per field.)
- **Titles and abstracts score "similar" for unrelated studies.** The demo
  text is templated ("X Birth Cohort is a longitudinal..."), so cosine sees
  most words in common. Real abstracts will vary more, but a title is a name,
  not prose: comparing it as text is probably wrong.
- **Hierarchies aren't understood.** `United Kingdom` against `Northern
  Ireland` scores 0, though one contains the other. Geography needs its own
  comparison (post-MVP).
- **Related fields double up.** `publisher.name` and `publisher.gatewayId`
  always agree, so a different custodian shows as two different rows.
- **Dates are all or nothing.** Two start dates a month apart are "different";
  closeness by year would read better.
- **Keywords are harsh.** A study and its own wave share few of their (sampled)
  keywords, so Jaccard calls them different.
- **The schema is not returned.** The plan called for returning both GWDM
  schemas. The client already fetches the schema for the dataset page, so it
  labels fields from that; the response carries `gwdmVersion` to pick it.
