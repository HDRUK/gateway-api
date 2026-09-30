<?php

namespace Tests\Fakes;

use App\Services\TypesenseService;
use Typesense\Client;

/**
 * In-memory stand-in for Typesense
 */
class FakeTypesenseService extends TypesenseService
{
    // Typesense's own default when a search omits max_facet_values.
    private const DEFAULT_MAX_FACET_VALUES = 10;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $documents = [];

    public function __construct()
    {
        parent::__construct(new Client([
            'api_key' => 'fake',
            'nodes'   => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
        ]));
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     */
    public function withDocuments(string $collection, array $documents): self
    {
        $clone = clone $this;
        $clone->documents[$collection] = $documents;

        return $clone;
    }

    public function rawSearch(string $collection, string $query, array $params = []): array
    {
        return $this->searchCollection($collection, $params);
    }

    public function multiSearch(array $searches): array
    {
        return [
            'results' => array_map(
                fn (array $search) => $this->searchCollection($search['collection'], $search),
                $searches
            ),
        ];
    }

    private function searchCollection(string $collection, array $params): array
    {
        $documents = $this->documents[$collection] ?? [];
        $fields = array_filter(explode(',', $params['facet_by'] ?? ''));
        $maxValues = (int) ($params['max_facet_values'] ?? self::DEFAULT_MAX_FACET_VALUES);

        return [
            'found'        => count($documents),
            'hits'         => [],
            'facet_counts' => array_map(
                fn (string $field) => [
                    'field_name' => $field,
                    'counts'     => $this->tallyFacet($documents, $field, $maxValues),
                ],
                array_values($fields)
            ),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array{value: string, count: int}>
     */
    private function tallyFacet(array $documents, string $field, int $maxValues): array
    {
        $tally = [];
        foreach ($documents as $document) {
            foreach ((array) ($document[$field] ?? []) as $value) {
                $key = is_bool($value) ? var_export($value, true) : (string) $value;
                $tally[$key] = ($tally[$key] ?? 0) + 1;
            }
        }

        $counts = array_map(
            fn ($value) => ['value' => (string) $value, 'count' => $tally[$value]],
            array_keys($tally)
        );

        // Count desc then value asc, so output is deterministic (Typesense's tie order isn't).
        usort($counts, fn ($a, $b) => [$b['count'], $a['value']] <=> [$a['count'], $b['value']]);

        return array_slice($counts, 0, $maxValues);
    }
}
