# Similar datasets

`POST /api/v2/search/similar/datasets` with `{"id": <dataset id>}` returns up to three datasets similar to the given one, in the same `{data: [...]}` hydrated shape as the search endpoints.

## How similarity is decided

Similarity means sharing words with the dataset's keywords and title, where a dataset matching any one word is a candidate. A single Typesense query can't express that. Typesense requires every word in a query, and `drop_tokens` only drops words from the ends, so the middle words are never searched on their own. Instead:

1. Look up the dataset's Typesense document (`filter_by: dataset_id:=<id>`). Not indexed → `200 {data: []}`.
2. Split its `keywords` and `title` into distinct lowercase words, drop stop words, and keep the first 10. Splitting on non-alphanumerics also removes Typesense query operators (`"`, a leading `-`).
3. Send one search per word in a single `multi_search` request, using the normal dataset `query_by` fields and weights. Each search excludes the source (`dataset_id:!=<id>`) and returns up to 50 candidates.
4. Rank the candidates by how many words they matched, then by best text-match score, then by dataset id so ties always come out in the same order. Keep the top three.

A Typesense failure returns a 500 rather than an empty list, so callers can tell an outage apart from "nothing similar". That includes a single failed sub-search, which `multi_search` reports inside a 200 response.

Both the source lookup and the per-word searches are scoped to the partner contexts the caller may read (`PartnerContext::readableContextsFor()`), the same as the main search. A source dataset outside those contexts is treated as not indexed.

## v1

`POST /api/v1/search/similar/datasets` is served by the same handler as v2, so it returns identical results. It is deprecated: every response carries `Deprecation`, `Sunset` (17 May 2027) and a `Link` to the v2 endpoint. See [API deprecation](../CONVENTIONS/api-deprecation.md).

## Testing

`FakeTypesenseService::withTextMatching()` keeps only documents containing every query word, which is enough to test the ranking. It doesn't model typos, prefixes or scores, so the ranking was also checked by hand against a real Typesense 30.2.

## Future: semantic search

Word matching misses datasets that describe the same thing in different words. The plan is to move to semantic similarity using Typesense embeddings.
