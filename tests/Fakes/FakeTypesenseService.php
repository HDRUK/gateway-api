<?php

namespace Tests\Fakes;

use App\Services\TypesenseService;
use Typesense\Client;
use Typesense\Exceptions\ObjectAlreadyExists;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\ServerError;

/**
 * In-memory stand-in for Typesense
 */
class FakeTypesenseService extends TypesenseService
{
    // Typesense's own default when a search omits max_facet_values.
    private const DEFAULT_MAX_FACET_VALUES = 10;

    // Typesense's own default when a search omits per_page.
    private const DEFAULT_PER_PAGE = 10;

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

    // The real one builds a new client; the fake has nothing to time out.
    public function withTimeout(int $seconds): TypesenseService
    {
        return $this;
    }

    // -------------------------------------------------------------------------
    // In-memory collections and aliases, following Typesense 30's semantics
    // (checked against a real server): aliases resolve for every collection
    // and document call, an alias can't take a collection's name, but a
    // collection CAN be created under an alias's name.
    // -------------------------------------------------------------------------

    /** @var array<string, array> Physical collection name => schema. */
    private array $collections = [];

    /** @var array<string, string> Alias => collection name. */
    private array $aliases = [];

    /** @var array<string, array<string, array>> Collection => id => document. */
    private array $stored = [];

    /** @var null|callable(array): ?string Returns an error for documents to reject. */
    private $rejectDocument = null;

    /** Makes importDocuments() reject documents for which $rule returns an error message. */
    public function rejectDocumentsWhen(callable $rule): self
    {
        $this->rejectDocument = $rule;

        return $this;
    }

    /** Documents in a collection or alias target, keyed by id. */
    public function storedDocuments(string $collection): array
    {
        return $this->stored[$this->resolve($collection)];
    }

    /** @return string[] Physical collection names. */
    public function collectionNames(): array
    {
        return array_keys($this->collections);
    }

    private function resolve(string $name): string
    {
        $name = $this->aliases[$name] ?? $name;

        if (! isset($this->collections[$name])) {
            throw new ObjectNotFound("Not Found: {$name}");
        }

        return $name;
    }

    public function listCollections(): array
    {
        return array_map(
            fn (string $name) => $this->getCollection($name),
            array_keys($this->collections)
        );
    }

    public function collectionExists(string $collection): bool
    {
        try {
            $this->resolve($collection);

            return true;
        } catch (ObjectNotFound) {
            return false;
        }
    }

    public function getCollection(string $collection): array
    {
        $name = $this->resolve($collection);

        return $this->collections[$name] + ['num_documents' => count($this->stored[$name])];
    }

    public function createCollection(array $schema): array
    {
        if (isset($this->collections[$schema['name']])) {
            throw new ObjectAlreadyExists("A collection with name `{$schema['name']}` already exists.");
        }

        $this->collections[$schema['name']] = $schema;
        $this->stored[$schema['name']] = [];

        return $schema;
    }

    public function dropCollection(string $collection): array
    {
        $name = $this->resolve($collection);
        $schema = $this->collections[$name];
        unset($this->collections[$name], $this->stored[$name]);

        return $schema;
    }

    public function aliasTarget(string $alias): ?string
    {
        return $this->aliases[$alias] ?? null;
    }

    public function upsertAlias(string $alias, string $collection): array
    {
        if (isset($this->collections[$alias])) {
            throw new ServerError("Name `{$alias}` conflicts with an existing collection name.");
        }

        $this->aliases[$alias] = $collection;

        return ['name' => $alias, 'collection_name' => $collection];
    }

    public function upsertDocument(string $collection, array $document): array
    {
        $this->stored[$this->resolve($collection)][(string) $document['id']] = $document;

        return $document;
    }

    public function importDocuments(string $collection, array $documents, array $options = []): array
    {
        $name = $this->resolve($collection);

        return array_map(function (array $document) use ($name) {
            $error = $this->rejectDocument ? ($this->rejectDocument)($document) : null;

            if ($error !== null) {
                return ['success' => false, 'error' => $error, 'document' => json_encode($document)];
            }

            $this->stored[$name][(string) $document['id']] = $document;

            return ['success' => true];
        }, $documents);
    }

    public function exportDocumentIds(string $collection): array
    {
        return array_map('strval', array_keys($this->stored[$this->resolve($collection)]));
    }

    public function deleteDocumentsByIds(string $collection, array $ids, int $batchSize = 250): int
    {
        $name = $this->resolve($collection);
        $deleted = 0;

        foreach ($ids as $id) {
            if (isset($this->stored[$name][(string) $id])) {
                unset($this->stored[$name][(string) $id]);
                $deleted++;
            }
        }

        return $deleted;
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
        $filterBy = $params['filter_by'] ?? '';
        $documents = array_values(array_filter(
            $this->documents[$collection] ?? [],
            fn (array $document) => $filterBy === '' || $this->matchesFilter($document, $filterBy)
        ));
        $fields = array_filter(explode(',', $params['facet_by'] ?? ''));
        $maxValues = (int) ($params['max_facet_values'] ?? self::DEFAULT_MAX_FACET_VALUES);
        $perPage = (int) ($params['per_page'] ?? self::DEFAULT_PER_PAGE);
        $page = max(1, (int) ($params['page'] ?? 1));

        return [
            'found'        => count($documents),
            'hits'         => array_map(
                fn (array $document) => ['document' => $document, 'text_match' => 1, 'highlight' => []],
                array_slice($documents, ($page - 1) * $perPage, $perPage)
            ),
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
     * Evaluates the subset of filter_by the app sends: clauses joined by
     * " && ", each an exact match (field:=value or field:=[a,b]) or a numeric
     * comparison (field:>n, >=, <, <=). Anything else throws, so a test can't
     * pass by having its filter silently ignored.
     */
    private function matchesFilter(array $document, string $filterBy): bool
    {
        foreach (explode(' && ', $filterBy) as $clause) {
            if (! $this->matchesClause($document, $clause)) {
                return false;
            }
        }

        return true;
    }

    private function matchesClause(array $document, string $clause): bool
    {
        if (preg_match('/^(\w+):(>=|<=|>|<)(-?\d+)$/', $clause, $m)) {
            $value = $document[$m[1]] ?? null;

            return is_numeric($value) && match ($m[2]) {
                '>='    => $value >= (int) $m[3],
                '<='    => $value <= (int) $m[3],
                '>'     => $value > (int) $m[3],
                '<'     => $value < (int) $m[3],
            };
        }

        if (! preg_match('/^(\w+):=(\[.*\]|[^\[\]]+)$/', $clause, $m)) {
            throw new \LogicException("FakeTypesenseService can't evaluate filter clause: {$clause}");
        }

        $list = trim($m[2], '[]');
        $wanted = str_contains($list, '`')
            ? (preg_match_all('/`([^`]*)`/', $list, $quoted) ? $quoted[1] : [])
            : explode(',', $list);
        $values = array_map(
            fn ($v) => is_bool($v) ? var_export($v, true) : (string) $v,
            (array) ($document[$m[1]] ?? [])
        );

        return array_intersect($values, $wanted) !== [];
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
