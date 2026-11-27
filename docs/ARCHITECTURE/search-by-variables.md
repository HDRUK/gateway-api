# Searching datasets by variables

`POST /api/v2/search` (and its export, `POST /api/v2/search/{type}/export`) accepts an optional `variables` list. With it, a dataset search returns only datasets holding a variable that matches **every** term, combined with the usual `query`, `filters` and `advanced`:

```json
{
  "type": "datasets",
  "query": "pregnancy",
  "variables": ["birthweight", "maternal depression"],
  "filters": { "dataset": { "publisherName": ["SAIL"] } }
}
```

The response shape doesn't change. To show which variables matched, call `POST /api/v2/search/variables` with the page's ids as `dataset_ids` (match `any`). See `App\Services\Search\VariableSearch`.

## Non-breaking by design

The search is shared by every portal, so `variables` is additive:

- Absent or `[]`: the search is exactly as before. No variable lookup happens and no clause is added.
- Only for `type: datasets`. Any other type gets a `422`.
- Only for partners whose variables are indexed (`typesense.variables.partners`, `VariableIndex::indexesPartner()`), and only when variable indexing is enabled. Otherwise it's a `422`, not an empty result, because "nothing matched" and "not available" must not look the same.
- Terms follow the variables endpoint's rules: 2–100 characters, at most 6, distinct regardless of case, and no filter syntax. Both requests share `VariableSearch::termRules()`.

Federated providers (ARDC etc.) search on the query string alone and ignore `variables`, as they do `filters` and `advanced`. That's intended.

## How it filters

1. `VariableSearch::matchingDatasetIds()` searches the variable index once per term, by words and by meaning, scoped to the caller's partner contexts. It keeps the datasets matching every term.
2. `HDRUK::filterClauses()` adds those ids as a `variables` clause, `dataset_id:=[…]`. Like `advanced`, it's keyed apart from the facet fields, so the facet exclusion, date-span and population-bucket queries all count within it.
3. With no matching dataset the clause is `dataset_id:=[0]`, so the search returns nothing. Dropping the clause would widen the search to every dataset.

Search analytics log the terms (`filters.variables`), not the id list.

## Limits

- Each term reaches at most **250 datasets** (one grouped page in `VariableSearch::termSearch()`). A term matching more is truncated silently, so the combined search can miss datasets past that point. This is fine for current catalogue sizes. Revisit it (paging the grouped search) if a partner's catalogue grows past it.
- `matchingDatasetIds()` is not capped at `typesense.variables.max_datasets`. That limit caps the variables endpoint's response, not a filter.
- Each term is a vector search (`k: 2000`), so a variables search costs up to 6 extra Typesense searches before the main one.

## Testing

- `tests/Unit/Http/Requests/Search/SearchRequestTest.php`: validation, including that searches without `variables` are unaffected.
- `tests/Unit/SearchProviders/HDRUKTest.php`, "variables" section: the clause, the empty-match case, supporting queries, partner scope, analytics and export.
- `tests/Unit/Services/Search/VariableSearchTest.php`: `matchingDatasetIds()`.
