<?php

namespace App\SearchProviders;

use Auditor;
use Http;
use App\Contracts\SearchProvider;
use App\Services\Search\FilterCache;
use App\Services\Search\CollectionHydrator;
use App\Services\Search\DataCustodianHydrator;
use App\Services\Search\DataCustodianNetworkHydrator;
use App\Services\Search\DatasetHydrator;
use App\Services\Search\DataUseHydrator;
use App\Services\Search\PublicationHydrator;
use App\Services\Search\ToolHydrator;
use Laravel\Pennant\Feature;

class HDRUK implements SearchProvider
{
    private const SERVICE_PATH_MAP = [
        'datasets'                => 'datasets',
        'tools'                   => 'tools',
        'collections'             => 'collections',
        'dur'                     => 'dur',
        'publications'            => 'publications',
        'data_custodian_networks' => 'data_custodian_networks',
        'data_custodians'         => 'data_providers',
    ];

    private const FILTER_TYPE_MAP = [
        'datasets'                => ['type' => 'dataset',          'enabledOnly' => true],
        'tools'                   => ['type' => 'tool',             'enabledOnly' => false],
        'collections'             => ['type' => 'collection',       'enabledOnly' => false],
        'dur'                     => ['type' => 'dataUseRegister',  'enabledOnly' => false],
        'publications'            => ['type' => 'paper',            'enabledOnly' => false],
        // 'datacustodiannetwork' (no camelCase/underscore) is what the `filters`
        // table and FE payloads actually use — confirmed against live DB rows.
        'data_custodian_networks' => ['type' => 'datacustodiannetwork', 'enabledOnly' => false],
        'data_custodians'         => ['type' => 'dataProvider',     'enabledOnly' => false],
    ];

    private const TYPESENSE_MODEL_MAP = [
        'datasets'                => \App\Models\DatasetVersion::class,
        'tools'                   => \App\Models\Tool::class,
        'collections'             => \App\Models\Collection::class,
        'dur'                     => \App\Models\Dur::class,
        'publications'            => \App\Models\Publication::class,
        'data_custodian_networks' => \App\Models\DataProviderColl::class,
        'data_custodians'         => \App\Models\Team::class,
    ];

    public function isDeferred(): bool
    {
        return false;
    }

    public function getFullName(): string
    {
        return 'Health Data Research UK';
    }

    public function getShortName(): string
    {
        return 'HDRUK';
    }

    public function getProviderLogo(): string|null
    {
        return null;
    }

    public function getProviderBlurb(): string|null
    {
        return null;
    }

    public function getSearchURI(string $type): string
    {
        $path = self::SERVICE_PATH_MAP[$type] ?? $type;
        return config('gateway.search_service_url') . "/search/{$path}";
    }

    public function getSupportedTypes(): array
    {
        return array_keys(self::SERVICE_PATH_MAP);
    }

    public function isTypesenseEnabled(): bool
    {
        return Feature::active('TypesenseSearch');
    }

    /**
     * @return array<string, class-string>  Search entity type => Scout model class.
     */
    public static function typesenseModelMap(): array
    {
        return self::TYPESENSE_MODEL_MAP;
    }

    private function modelClassForFilterType(string $filterType): ?string
    {
        foreach (self::FILTER_TYPE_MAP as $key => $config) {
            if ($config['type'] === $filterType && isset(self::TYPESENSE_MODEL_MAP[$key])) {
                return self::TYPESENSE_MODEL_MAP[$key];
            }
        }

        return null;
    }

    /**
     * Resolves a Filter model's (type, keys) pair — e.g. ('dataset', 'dataType')
     * — to the Typesense collection name backing it, but only if that field is
     * actually facet-enabled in the model's typesenseCollectionSchema(). Fields
     * not yet flattened/faceted for Typesense return null so callers can fall
     * back to the legacy Elasticsearch filter service.
     */
    public function collectionForFacetableFilter(string $filterType, string $field): ?string
    {
        $modelClass = $this->modelClassForFilterType($filterType);
        if ($modelClass === null) {
            return null;
        }

        $model = new $modelClass();
        $facetable = collect($model->typesenseCollectionSchema()['fields'] ?? [])
            ->contains(fn ($f) => $f['name'] === $field && ($f['facet'] ?? false));

        return $facetable ? $model->searchableAs() : null;
    }

    public function search(string $query, string $type, array $params = []): array
    {
        try {
            // Always offer a fallback in case of mismatched model map
            if (!$this->isTypesenseEnabled() || !array_key_exists($type, self::TYPESENSE_MODEL_MAP)) {
                return $this->searchViaElastic($query, $type, $params);
            }

            return $this->searchViaTypesense($query, $type, $params);
        } catch (\Throwable $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::error($e->getMessage());
        }

        return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
    }

    private function searchViaTypesense(string $query, string $type, array $params): array
    {
        $modelClass = self::TYPESENSE_MODEL_MAP[$type];
        $model      = new $modelClass();
        $collection = $model->searchableAs();
        $q          = $query ?: '*';

        $searchParams = array_merge([
            // Typesense's default (1) stops widening the query as soon as ANY
            // result is found, so a query spanning multiple distinct terms
            // (e.g. "TOWNSEND_2011_QUINTILE, AGEM Derived...", pasted from two
            // different datasets' metadata) only ever returns hits for
            // whichever term matched best — it never relaxes further to pick
            // up the other term(s). Raising this lets Typesense keep dropping
            // tokens until it has found a reasonable number of results across
            // ALL the query's distinct terms, not just the first one matched.
            'drop_tokens_threshold' => 15,
        ], $model->typesenseSearchParameters(), [
            'per_page' => (int) ($params['per_page'] ?? 20),
            'page'     => (int) ($params['page'] ?? 1),
        ]);

        $facetFields = array_values(array_filter(explode(',', config("typesense.facet_map.{$type}", ''))));
        if (!empty($facetFields)) {
            $searchParams['facet_by'] = implode(',', $facetFields);
        }

        $clauses          = $this->buildFilterClauses($type, $params);
        $combinedFilterBy = implode(' && ', $clauses);
        if ($combinedFilterBy !== '') {
            $searchParams['filter_by'] = $combinedFilterBy;
        }

        // Multi-select faceting: a field with its own active filter must
        // still report counts for ALL its values, not just the one(s)
        // selected — so its facet needs a second query with every filter
        // applied EXCEPT its own. Fields with no filter of their own are
        // unaffected and use the main query's facet_counts as-is.
        $selfFilteredFields = array_values(array_intersect($facetFields, array_keys($clauses)));

        $searches = [array_merge(['collection' => $collection, 'q' => $q], $searchParams)];
        foreach ($selfFilteredFields as $field) {
            $exclusionFilterBy = implode(' && ', array_diff_key($clauses, [$field => null]));

            $searches[] = array_merge(
                [
                    'collection' => $collection,
                    'q'          => $q,
                    'query_by'   => $searchParams['query_by'],
                    'facet_by'   => $field,
                    'per_page'   => 0,
                ],
                $exclusionFilterBy !== '' ? ['filter_by' => $exclusionFilterBy] : []
            );
        }

        $multiResult = app(\App\Services\TypesenseService::class)->multiSearch($searches);
        $results     = $multiResult['results'] ?? [];
        $result      = $results[0] ?? [];

        $facetCounts = $result['facet_counts'] ?? [];
        foreach (array_slice($results, 1) as $i => $exclusionResult) {
            $field = $selfFilteredFields[$i];
            $facetCounts = array_values(array_filter(
                $facetCounts,
                fn ($f) => $f['field_name'] !== $field
            ));
            foreach ($exclusionResult['facet_counts'] ?? [] as $facet) {
                $facetCounts[] = $facet;
            }
        }

        $elasticHits = $this->mapHitsToElastic($result['hits'] ?? [], $type);
        $hydrated    = $this->hydrate($elasticHits, $type, $params['view_type'] ?? 'full');
        $sorted      = $this->sort($hydrated, $type, $params['sort'] ?? 'score:desc');

        return [
            'source'       => 'typesense',
            'hits'         => $sorted,
            'total'        => $result['found'] ?? 0,
            'aggregations' => $this->mapFacetsToAggregations($facetCounts),
            'ids'          => array_column($elasticHits, '_id'),
        ];
    }

    private function searchViaElastic(string $query, string $type, array $params): array
    {
        $filterConfig = self::FILTER_TYPE_MAP[$type] ?? ['type' => $type, 'enabledOnly' => false];

        $input         = $params;
        $input['aggs'] = FilterCache::get($filterConfig['type'], $filterConfig['enabledOnly']);

        if ($query !== '') {
            $input['query'] = $query;
        }

        $response = Http::post($this->getSearchURI($type), $input);

        if (!$response->successful()) {
            return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
        }

        $body = $response->json();

        if (
            !isset($body['hits']) || !is_array($body['hits']) ||
            !isset($body['hits']['hits']) || !is_array($body['hits']['hits']) ||
            !isset($body['hits']['total']['value'])
        ) {
            return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
        }

        $rawHits = $body['hits']['hits'];
        $total   = $body['hits']['total']['value'];
        $ids     = array_column($rawHits, '_id');
        $aggs    = $body['aggregations'] ?? [];

        $hydrated = $this->hydrate($rawHits, $type, $params['view_type'] ?? 'full');
        $sorted   = $this->sort($hydrated, $type, $params['sort'] ?? 'score:desc');

        return [
            'hits'         => $sorted,
            'total'        => $total,
            'aggregations' => $aggs,
            'ids'          => $ids,
        ];
    }

    /**
     * Convert Typesense hits to the Elasticsearch-shaped array the hydrators expect.
     * Datasets use dataset_id as _id so DatasetHydrator looks up the right parent record.
     */
    private function mapHitsToElastic(array $typesenseHits, string $type): array
    {
        return array_map(function (array $hit) use ($type) {
            $doc = $hit['document'] ?? [];
            $id  = ($type === 'datasets')
                ? ($doc['dataset_id'] ?? $doc['id'])
                : $doc['id'];

            return [
                '_id'     => $id,
                '_score'  => $hit['text_match'] ?? 1,
                '_source' => $doc,
            ];
        }, $typesenseHits);
    }

    /**
     * Convert Typesense facet_counts to the ES aggregations shape the frontend expects:
     * { fieldName: { buckets: [{ key, doc_count }] } }
     */
    private function mapFacetsToAggregations(array $facetCounts): array
    {
        $aggs = [];
        foreach ($facetCounts as $facet) {
            $aggs[$facet['field_name']] = [
                'buckets' => array_map(fn ($c) => [
                    'key'       => $c['value'],
                    'doc_count' => $c['count'],
                ], $facet['counts'] ?? []),
            ];
        }
        return $aggs;
    }

    /**
     * Build a map of field => Typesense filter_by clause from the V2
     * request's filter values, nested as
     * filters: { [Filter.type]: { [field]: [...values] } } — keyed by the
     * singular Filter model type (e.g. 'dataset'), same as
     * FILTER_TYPE_MAP/collectionForFacetableFilter, not the plural service
     * key used elsewhere in this class. Only known facet fields are
     * forwarded to avoid leaking arbitrary input into the filter. Fields not
     * yet supporting array-of-string values (e.g. populationSize's range
     * object) are silently ignored rather than erroring.
     *
     * Keeping this keyed by field (rather than one joined string) lets
     * searchViaTypesense() build a "every filter except this field's own"
     * variant per facet, for multi-select faceting.
     *
     * @return array<string, string>
     */
    private function buildFilterClauses(string $type, array $params): array
    {
        $fields      = config("typesense.filterable_map.{$type}", []);
        $filterType  = self::FILTER_TYPE_MAP[$type]['type'] ?? $type;
        $typeFilters = $params['filters'][$filterType] ?? [];
        $clauses     = [];

        foreach ($fields as $field) {
            $isBoolean = $this->isBooleanFacetField($type, $field);
            $values    = $this->normalizeFilterValues($typeFilters[$field] ?? null);

            // Checkbox-style boolean filters (e.g. a "Datasets with BioSamples"
            // toggle) arrive as a flat top-level query param — non-empty
            // (typically the field's own name, e.g.
            // ?containsBioSamples=containsBioSamples) when checked, but when
            // unchecked the key can still be PRESENT with an empty string
            // (?isCohortDiscovery=) rather than fully absent — so presence
            // alone isn't enough; the value must be non-empty too. Presence
            // means "true"; empty/absent means "no filter" (never "false"),
            // matching normal single-checkbox UX.
            if (empty($values) && $isBoolean && !empty($params[$field] ?? '')) {
                $values = ['true'];
            }

            if (empty($values)) {
                continue;
            }

            if ($isBoolean) {
                $clause = $this->buildBooleanClause($field, $values);
                if ($clause !== null) {
                    $clauses[$field] = $clause;
                }
                continue;
            }

            $quoted          = array_map(fn ($v) => '`' . str_replace('`', '', $v) . '`', $values);
            $clauses[$field] = $field . ':=[' . implode(',', $quoted) . ']';
        }

        return $clauses;
    }

    /**
     * Typesense's bool fields filter as a bare `field:=true`/`field:=false`,
     * not the quoted-array syntax used for string/string[] facets — that
     * syntax silently matches zero rows against a bool field rather than
     * erroring, so this must be detected and handled separately. Derived
     * from the model's schema (not a hardcoded field list) so it can't drift
     * out of sync with config/typesense.php's facet_map.
     */
    private function isBooleanFacetField(string $type, string $field): bool
    {
        $modelClass = self::TYPESENSE_MODEL_MAP[$type] ?? null;
        if ($modelClass === null) {
            return false;
        }

        $fieldDef = collect((new $modelClass())->typesenseCollectionSchema()['fields'] ?? [])
            ->firstWhere('name', $field);

        return ($fieldDef['type'] ?? null) === 'bool';
    }

    /**
     * Builds a bare (unquoted, non-array) boolean filter_by clause. Returns
     * null — no filter — if the selection is empty or ambiguous (both true
     * and false selected is equivalent to no filter at all).
     */
    private function buildBooleanClause(string $field, array $values): ?string
    {
        $normalized = array_values(array_unique(array_map(
            fn ($v) => strtolower(trim($v)),
            $values
        )));

        if (count($normalized) !== 1 || !in_array($normalized[0], ['true', 'false'], true)) {
            return null;
        }

        return $field . ':=' . $normalized[0];
    }

    /**
     * Accepts an array of filter values (the current V2 filter shape) and
     * returns their string representations, trimmed and non-empty. Scalars
     * (string, bool, int, float) are all coerced to a string — e.g. a
     * literal JSON `true` in `isCohortDiscovery: [true]` becomes 'true' here
     * so buildBooleanClause() can recognize it, not just a pre-stringified
     * "true". Non-scalars — associative "structured" filters like
     * populationSize's {includeUnreported}, nested arrays — normalize to []
     * rather than erroring, since those aren't supported filter_by shapes yet.
     */
    private function normalizeFilterValues(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            function ($v) {
                if (is_string($v)) {
                    return trim($v);
                }
                if (is_bool($v)) {
                    return $v ? 'true' : 'false';
                }
                if (is_int($v) || is_float($v)) {
                    return (string) $v;
                }
                return null;
            },
            $value
        ), fn ($v) => $v !== null && $v !== ''));
    }

    private function sort(array $hits, string $type, string $sortParam): array
    {
        $parts     = explode(':', $sortParam, 2);
        $rawField  = $parts[0];
        $direction = $parts[1] ?? 'desc';

        $field = ($type === 'datasets' && $rawField === 'title') ? 'shortTitle' : $rawField;

        if ($field === 'score') {
            return $direction === 'desc' ? $hits : array_reverse($hits);
        }

        usort($hits, function ($a, $b) use ($field, $direction) {
            $aVal = $a['_source'][$field] ?? null;
            $bVal = $b['_source'][$field] ?? null;

            if (is_string($aVal) && strtotime($aVal) !== false) {
                $cmp = strtotime((string)$aVal) <=> strtotime((string)$bVal);
            } elseif (is_string($aVal)) {
                $cmp = strtoupper((string)$aVal) <=> strtoupper((string)$bVal);
            } else {
                $cmp = $aVal <=> $bVal;
            }

            return $direction === 'asc' ? $cmp : -$cmp;
        });

        return $hits;
    }

    private function hydrate(array $hits, string $type, string $viewType = 'full'): array
    {
        return match ($type) {
            'datasets'                => (new DatasetHydrator())->hydrate($hits, $viewType),
            'tools'                   => (new ToolHydrator())->hydrate($hits),
            'collections'             => (new CollectionHydrator())->hydrate($hits),
            'dur'                     => (new DataUseHydrator())->hydrate($hits),
            'publications'            => (new PublicationHydrator())->hydrate($hits),
            'data_custodian_networks' => (new DataCustodianNetworkHydrator())->hydrate($hits),
            'data_custodians'         => (new DataCustodianHydrator())->hydrate($hits),
            default                   => $hits,
        };
    }
}
