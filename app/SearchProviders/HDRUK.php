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
use App\Context\PartnerContext;
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

    // Fixed log-scale buckets matching the production Elasticsearch range aggregation.
    // Keys and range boundaries are intentionally identical so the FE renders identically.
    private const POPULATION_SIZE_BUCKETS = [
        ['key' => 'Unreported',         'from' => -1,        'to' => 1],
        ['key' => '1.0-10.0',           'from' => 1,         'to' => 10],
        ['key' => '10.0-100.0',         'from' => 10,        'to' => 100],
        ['key' => '100.0-1000.0',       'from' => 100,       'to' => 1000],
        ['key' => '1000.0-10000.0',     'from' => 1000,      'to' => 10000],
        ['key' => '10000.0-100000.0',   'from' => 10000,     'to' => 100000],
        ['key' => '100000.0-1000000.0', 'from' => 100000,    'to' => 1000000],
        ['key' => '1000000.0-1.0E7',    'from' => 1000000,   'to' => 10000000],
        ['key' => '1.0E7-1.0E8',        'from' => 10000000,  'to' => 100000000],
        ['key' => '1.0E8-1.0E9',        'from' => 100000000, 'to' => 1000000000],
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
            'drop_tokens_threshold'            => 15,
            // Typesense truncates facet values to 100 chars by default —
            // the FE then sends the truncated string as a filter value, which
            // never matches the full stored value. 0 = no truncation.
            'facet_value_truncation_threshold' => 0,
        ], $model->typesenseSearchParameters(), [
            'per_page' => (int) ($params['per_page'] ?? 20),
            'page'     => (int) ($params['page'] ?? 1),
        ]);

        $facetFields = array_values(array_filter(explode(',', config("typesense.facet_map.{$type}", ''))));
        if (!empty($facetFields)) {
            $searchParams['facet_by'] = implode(',', $facetFields);
        }

        $clauses = $this->buildFilterClauses($type, $params);

        // Inject the partner context scope. Using $clauses ensures the filter
        // is automatically AND-ed into every downstream query (exclusion,
        // date-range min/max, population buckets) without touching any of that
        // code — partnerContext is not in facet_map, so it is never stripped
        // by the multi-select exclusion-query logic.
        $partnerFilter = $this->buildPartnerContextFilter($type);
        if ($partnerFilter !== null) {
            $clauses['partnerContext'] = $partnerFilter;
        }

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
                    'collection'                       => $collection,
                    'q'                                => $q,
                    'query_by'                         => $searchParams['query_by'],
                    'facet_by'                         => $field,
                    'per_page'                         => 0,
                    // Keep truncation off so bucket keys match the stored values
                    // — see TypesenseService::facetCounts() for the same fix.
                    'facet_value_truncation_threshold' => 0,
                ],
                $exclusionFilterBy !== '' ? ['filter_by' => $exclusionFilterBy] : []
            );
        }

        // Date range aggregations: find the earliest startDate and latest endDate
        // across the current (non-date-filtered) result set, so the FE year picker
        // knows the full span of available data. We exclude any active date range
        // clauses from these queries so the picker isn't constrained by its own filter.
        $nonDateClauses    = array_diff_key($clauses, ['startDate' => null, 'endDate' => null]);
        $nonDateFilterBy   = implode(' && ', $nonDateClauses);
        $startDateMinIdx   = count($searches);
        $searches[]        = array_merge(
            ['collection' => $collection, 'q' => '*', 'query_by' => $searchParams['query_by'], 'sort_by' => 'startDate:asc', 'per_page' => 1],
            ['filter_by' => ($nonDateFilterBy !== '' ? $nonDateFilterBy . ' && ' : '') . 'startDate:>0']
        );
        $endDateMaxIdx     = count($searches);
        $searches[]        = array_merge(
            ['collection' => $collection, 'q' => '*', 'query_by' => $searchParams['query_by'], 'sort_by' => 'endDate:desc', 'per_page' => 1],
            ['filter_by' => ($nonDateFilterBy !== '' ? $nonDateFilterBy . ' && ' : '') . 'endDate:>0']
        );

        // Population size histogram: one count query per fixed log-scale bucket so the
        // FE slider and histogram bars match the production Elasticsearch range aggregation.
        // We exclude the active populationSize clause (if any) so multi-select works —
        // the same pattern as self-filtered string facets above.
        $popBucketStartIdx = null;
        if ($type === 'datasets') {
            $nonPopClauses     = array_diff_key($clauses, ['populationSize' => null]);
            $nonPopFilterBy    = implode(' && ', $nonPopClauses);
            $popBucketStartIdx = count($searches);

            foreach (self::POPULATION_SIZE_BUCKETS as $bucket) {
                $bucketFilter = "populationSize:>={$bucket['from']} && populationSize:<{$bucket['to']}";
                $fullFilter   = $nonPopFilterBy !== '' ? $nonPopFilterBy . ' && ' . $bucketFilter : $bucketFilter;
                $searches[]   = [
                    'collection' => $collection,
                    'q'          => $q,
                    'query_by'   => $searchParams['query_by'],
                    'per_page'   => 0,
                    'filter_by'  => $fullFilter,
                ];
            }
        }

        $multiResult = app(\App\Services\TypesenseService::class)->multiSearch($searches);
        $results     = $multiResult['results'] ?? [];
        $result      = $results[0] ?? [];

        $facetCounts = $result['facet_counts'] ?? [];
        foreach (array_slice($results, 1, count($selfFilteredFields)) as $i => $exclusionResult) {
            $field = $selfFilteredFields[$i];
            $facetCounts = array_values(array_filter(
                $facetCounts,
                fn ($f) => $f['field_name'] !== $field
            ));
            foreach ($exclusionResult['facet_counts'] ?? [] as $facet) {
                $facetCounts[] = $facet;
            }
        }

        $dateRangeAggs = $this->buildDateRangeAggregations(
            $results[$startDateMinIdx]['hits'][0]['document'] ?? null,
            $results[$endDateMaxIdx]['hits'][0]['document'] ?? null,
        );

        $popSizeAgg = [];
        if ($popBucketStartIdx !== null) {
            $buckets = [];
            foreach (self::POPULATION_SIZE_BUCKETS as $i => $bucket) {
                $buckets[] = [
                    'key'       => $bucket['key'],
                    'from'      => $bucket['from'],
                    'to'        => $bucket['to'],
                    'doc_count' => $results[$popBucketStartIdx + $i]['found'] ?? 0,
                ];
            }
            $popSizeAgg = ['populationSize' => ['buckets' => $buckets]];
        }

        $elasticHits = $this->mapHitsToElastic($result['hits'] ?? [], $type);
        $hydrated    = $this->hydrate($elasticHits, $type, $params['view_type'] ?? 'full');
        $sorted      = $this->sort($hydrated, $type, $params['sort'] ?? 'score:desc');

        return [
            'source'       => 'typesense',
            'hits'         => $sorted,
            'total'        => $result['found'] ?? 0,
            'aggregations' => array_merge($this->mapFacetsToAggregations($facetCounts), $dateRangeAggs, $popSizeAgg),
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
     *
     * Date range aggregations (startDate/endDate) are handled separately by
     * buildDateRangeAggregations() using dedicated min/max queries — not facets.
     */
    private function mapFacetsToAggregations(array $facetCounts): array
    {
        $aggs = [];
        foreach ($facetCounts as $facet) {
            $aggs[$facet['field_name']] = [
                'buckets' => array_map(fn ($c) => [
                    'key'       => (string) $c['value'],
                    'doc_count' => $c['count'],
                ], $facet['counts'] ?? []),
            ];
        }
        return $aggs;
    }

    /**
     * Build ES-style min/max date aggregations from the documents returned by
     * the dedicated startDate:asc / endDate:desc sort queries.
     *
     * The FE date picker expects:
     *   startDate: { value: <ms_timestamp>, value_as_string: "YYYY-MM-DDT..." }
     *   endDate:   { value: <ms_timestamp>, value_as_string: "YYYY-MM-DDT..." }
     *
     * Typesense stores timestamps in seconds; ES returns them in milliseconds,
     * so we multiply by 1000 for compatibility.
     */
    private function buildDateRangeAggregations(?array $startDoc, ?array $endDoc): array
    {
        $aggs = [];

        if ($startDoc !== null && isset($startDoc['startDate'])) {
            $ts = (int) $startDoc['startDate'];
            $aggs['startDate'] = [
                'value'           => $ts * 1000,
                'value_as_string' => gmdate('Y-m-d\TH:i:s.000\Z', $ts),
            ];
        }

        if ($endDoc !== null && isset($endDoc['endDate'])) {
            $ts = (int) $endDoc['endDate'];
            $aggs['endDate'] = [
                'value'           => $ts * 1000,
                'value_as_string' => gmdate('Y-m-d\TH:i:s.000\Z', $ts),
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
     * forwarded to avoid leaking arbitrary input into the filter. Fields with
     * non-array filter shapes (e.g. populationSize's {from,to,includeUnreported}
     * object) are handled after this loop by dedicated clause builders.
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
            // Date-range fields carry a {from, to} object — normalizeFilterValues()
            // would return [] for any associative array, so check these first to
            // prevent the empty-values guard below from skipping them entirely.
            if ($this->isDateRangeField($type, $field)) {
                $clause = $this->buildDateRangeClause($field, $typeFilters[$field] ?? null);
                if ($clause !== null) {
                    $clauses[$field] = $clause;
                }
                continue;
            }

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

        // populationSize sends a {from, to, includeUnreported} object — not an array
        // of string values — so it lives outside the filterable_map loop.
        $popRaw = $typeFilters['populationSize'] ?? null;
        if (is_array($popRaw)) {
            $clause = $this->buildPopulationSizeClause($popRaw);
            if ($clause !== null) {
                $clauses['populationSize'] = $clause;
            }
        }

        return $clauses;
    }

    /**
     * Date range fields are stored as int64 Unix timestamps in Typesense.
     * They are filtered with >= / <= operators, not the string-array syntax.
     */
    private function isDateRangeField(string $type, string $field): bool
    {
        $modelClass = self::TYPESENSE_MODEL_MAP[$type] ?? null;
        if ($modelClass === null) {
            return false;
        }

        $fieldDef = collect((new $modelClass())->typesenseCollectionSchema()['fields'] ?? [])
            ->firstWhere('name', $field);

        return ($fieldDef['type'] ?? null) === 'int64'
            && in_array($field, ['startDate', 'endDate'], true);
    }

    /**
     * Builds a Typesense range clause for a date field.
     *
     * The FE sends a {from, to} object where either key may be absent:
     *   filters.dataset.startDate = { from: "2020-01-01", to: "2023-12-31" }
     *
     * Coverage overlap semantics: show datasets whose sample period overlaps
     * the selected window, i.e. dataset.startDate <= to AND dataset.endDate >= from.
     *
     *   startDate filter → dataset.startDate:<=toTimestamp
     *   endDate   filter → dataset.endDate:>=fromTimestamp
     */
    private function buildDateRangeClause(string $field, mixed $raw): ?string
    {
        if (!is_array($raw) || empty($raw)) {
            return null;
        }

        $parts = [];

        if ($field === 'startDate' && !empty($raw['to'])) {
            // Overlap: dataset's start must be on or before the query's upper bound.
            $ts = strtotime($this->yearToDate((string) $raw['to'], 'to'));
            if ($ts !== false) {
                $parts[] = "startDate:<={$ts}";
            }
        }

        if ($field === 'endDate' && !empty($raw['from'])) {
            // Overlap: dataset's end must be on or after the query's lower bound.
            $ts = strtotime($this->yearToDate((string) $raw['from'], 'from'));
            if ($ts !== false) {
                $parts[] = "endDate:>={$ts}";
            }
        }

        return !empty($parts) ? implode(' && ', $parts) : null;
    }

    /**
     * Returns a Typesense filter clause that scopes search results to the active
     * partner, mirroring Dataset::scopeForPartnerContext().
     *
     * HDRUK with allow_cross_context_read=true sees all partners (no clause).
     * Any other partner — or HDRUK with cross-context read disabled — is
     * restricted to its own datasets.
     *
     * Only applies to the 'datasets' type; other entity types don't carry a
     * partner_context column and shouldn't be filtered.
     */
    private function buildPartnerContextFilter(string $type): ?string
    {
        if ($type !== 'datasets') {
            return null;
        }

        $partner = app(PartnerContext::class)->getPartner();

        $shouldFilter = $partner && ($partner !== 'HDRUK' || !config('partners.allow_cross_context_read', false));

        if (!$shouldFilter) {
            return null;
        }

        return 'partnerContext:=`' . str_replace('`', '', $partner) . '`';
    }

    /**
     * Builds a Typesense filter_by clause for the populationSize range filter.
     *
     * The FE sends: { from: int, to: int, includeUnreported: bool }
     * Any key may be absent. `includeUnreported` ORs in `populationSize:=-1`
     * so datasets without a recorded size appear alongside the range results.
     *
     * Only produces a clause when there is something to constrain — if neither
     * from/to nor includeUnreported=false is present, null is returned (no filter).
     */
    private function buildPopulationSizeClause(array $raw): ?string
    {
        $from              = isset($raw['from']) && is_numeric($raw['from']) ? (int) $raw['from'] : null;
        $to                = isset($raw['to'])   && is_numeric($raw['to']) ? (int) $raw['to'] : null;
        $includeUnreported = (bool) ($raw['includeUnreported'] ?? true);

        $rangeParts = [];
        if ($from !== null) {
            $rangeParts[] = "populationSize:>={$from}";
        }
        if ($to !== null) {
            $rangeParts[] = "populationSize:<={$to}";
        }

        // No range set → nothing to constrain. includeUnreported only modifies
        // range semantics; it doesn't filter standalone (default FE state is
        // {includeUnreported: false} with no slider position, which must return
        // all datasets — not exclude the 600+ with no recorded population size).
        if (empty($rangeParts)) {
            return null;
        }

        $rangeClause = implode(' && ', $rangeParts);

        return $includeUnreported
            ? "({$rangeClause}) || populationSize:=-1"
            : $rangeClause;
    }

    /**
     * Expand a bare 4-digit year string (e.g. "2026") to a full ISO date so
     * strtotime() doesn't misread it as a time ("20:26" today). For the upper
     * bound use the last day of the year; for the lower bound the first day.
     */
    private function yearToDate(string $value, string $bound): string
    {
        if (preg_match('/^\d{4}$/', trim($value))) {
            return $bound === 'from' ? "{$value}-01-01" : "{$value}-12-31";
        }

        return $value;
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
