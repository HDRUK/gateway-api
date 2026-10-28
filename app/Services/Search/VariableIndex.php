<?php

namespace App\Services\Search;

use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\TypesenseService;
use Illuminate\Support\Facades\Log;

/**
 * The Typesense collection behind variable search: one document per column
 * of a dataset's structural metadata, embedded from its readable name and
 * description so a search for "depression measures" finds "PHQ-9 total".
 *
 * Only the dataset's indexed version (DatasetVersion::shouldBeSearchable)
 * contributes, so the variables always match what dataset search shows.
 */
class VariableIndex
{
    public function __construct(private readonly TypesenseService $typesense) {}

    /**
     * Variable indexing follows Scout's driver, so tests and environments
     * without Typesense skip it rather than fail.
     */
    public static function enabled(): bool
    {
        return config('scout.driver') === 'typesense'
            && (bool) config('typesense.variables.enabled', true);
    }

    public static function collectionName(): string
    {
        return config('scout.prefix', '').config('typesense.variables.collection', 'dataset_variables');
    }

    public function schema(): array
    {
        return [
            'name' => self::collectionName(),
            'fields' => [
                ['name' => 'dataset_id',     'type' => 'string', 'facet' => true],
                ['name' => 'datasetTitle',   'type' => 'string', 'optional' => true],
                ['name' => 'publisherName',  'type' => 'string', 'optional' => true],
                ['name' => 'partnerContext', 'type' => 'string', 'optional' => true],
                ['name' => 'tableName',      'type' => 'string', 'optional' => true],
                ['name' => 'tableIndex',     'type' => 'int32'],
                ['name' => 'columnIndex',    'type' => 'int32'],
                ['name' => 'name',           'type' => 'string'],
                // The column name with separators as spaces ("ghq12 score"),
                // so both keyword matching and the embedding read it as words.
                ['name' => 'label',          'type' => 'string'],
                ['name' => 'description',    'type' => 'string', 'optional' => true],
                ['name' => 'dataType',       'type' => 'string', 'optional' => true],
                [
                    'name' => 'embedding',
                    'type' => 'float[]',
                    'embed' => [
                        'from' => ['label', 'description'],
                        'model_config' => ['model_name' => config('typesense.variables.model')],
                    ],
                ],
            ],
            'default_sorting_field' => 'columnIndex',
        ];
    }

    /**
     * Drops and recreates the collection, then indexes every eligible
     * dataset. The first create downloads the embedding model, which can
     * take a minute or two.
     *
     * @param  callable(int $datasetId, int $count): void|null  $progress
     */
    public function rebuild(?callable $progress = null): int
    {
        $name = self::collectionName();
        if ($this->typesense->collectionExists($name)) {
            $this->typesense->dropCollection($name);
        }
        $this->typesense->createCollection($this->schema());

        $total = 0;
        DatasetVersion::query()
            ->indexEligible()
            ->with(['dataset' => fn ($q) => $q->select(['id', 'status', 'team_id', 'partner_context'])->with('team:id,name')])
            ->chunkById(100, function ($versions) use (&$total, $progress) {
                foreach ($versions as $version) {
                    if (! $version->shouldBeSearchable()) {
                        continue;
                    }
                    $count = $this->import($this->documentsFor($version));
                    $total += $count;
                    if ($progress) {
                        $progress((int) $version->dataset_id, $count);
                    }
                }
            });

        return $total;
    }

    /**
     * Replaces one dataset's variables with those of its indexed version,
     * or removes them when no version is indexed.
     */
    public function syncDataset(int $datasetId): int
    {
        $this->ensureCollection();
        $this->removeDataset($datasetId);

        $version = DatasetVersion::query()
            ->where('dataset_id', $datasetId)
            ->orderByDesc('version')
            ->first();

        if (! $version || ! $version->shouldBeSearchable()) {
            return 0;
        }

        return $this->import($this->documentsFor($version));
    }

    public function removeDataset(int $datasetId): void
    {
        $this->typesense->client()
            ->collections[self::collectionName()]
            ->documents
            ->delete(['filter_by' => 'dataset_id:='.$datasetId]);
    }

    /**
     * The variable documents for a dataset version, read from the
     * reconstructed GWDM envelope (delta versions carry no metadata).
     */
    public function documentsFor(DatasetVersion $version): array
    {
        $envelope = app(DatasetService::class)->getReconstructedMetadataEnvelope(
            $version->dataset_id,
            $version->version,
            false,
            $version,
        );

        $tables = data_get($envelope, 'metadata.structuralMetadata', []);
        if (is_string($tables)) {
            $tables = json_decode($tables, true) ?? [];
        }
        if (! is_array($tables) || ! array_is_list($tables)) {
            return [];
        }

        $base = [
            'dataset_id' => (string) $version->dataset_id,
            'datasetTitle' => (string) ($version->title ?? data_get($envelope, 'metadata.summary.title', '')),
            'publisherName' => (string) ($version->dataset?->team?->name ?? ''),
            'partnerContext' => (string) ($version->dataset?->partner_context ?? 'HDRUK'),
        ];

        $documents = [];
        foreach ($tables as $tableIndex => $table) {
            foreach (array_values((array) ($table['columns'] ?? [])) as $columnIndex => $column) {
                $name = trim((string) ($column['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $documents[] = $base + [
                    'id' => "{$version->dataset_id}-{$tableIndex}-{$columnIndex}",
                    'tableName' => (string) ($table['name'] ?? ''),
                    'tableIndex' => $tableIndex,
                    'columnIndex' => $columnIndex,
                    'name' => $name,
                    'label' => self::labelFor($name),
                    'description' => trim((string) ($column['description'] ?? '')),
                    'dataType' => (string) ($column['dataType'] ?? ''),
                ];
            }
        }

        return $documents;
    }

    public static function labelFor(string $name): string
    {
        return trim(preg_replace('/[\s_.\-]+/', ' ', $name));
    }

    private function import(array $documents): int
    {
        if ($documents === []) {
            return 0;
        }

        $results = $this->typesense->client()
            ->collections[self::collectionName()]
            ->documents
            ->import($documents, ['action' => 'upsert']);

        $failed = array_filter($results, fn ($r) => ! ($r['success'] ?? false));
        if ($failed !== []) {
            Log::warning('VariableIndex: some variables failed to index', [
                'dataset_id' => $documents[0]['dataset_id'],
                'failed' => count($failed),
                'first_error' => reset($failed)['error'] ?? null,
            ]);
        }

        return count($documents) - count($failed);
    }

    private function ensureCollection(): void
    {
        if (! $this->typesense->collectionExists(self::collectionName())) {
            $this->typesense->createCollection($this->schema());
        }
    }
}
