<?php

namespace App\Services\Search;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use App\Services\TypesenseService;
use Illuminate\Support\Facades\Log;

/**
 * The Typesense collection behind summary metadata search: one document per field in the summary section.metadata, embedded from its readable name and
 * description so a search for "depression measures" finds "PHQ-9 total".
 *
 * Only the dataset's indexed version (DatasetVersion::shouldBeSearchable)
 * contributes, so the variables always match what dataset search shows.
 */
class MetadataSummaryIndex
{
    private ?TypesenseService $writer = null;

    public function __construct(private readonly TypesenseService $typesense)
    {
    }

    /**
     * Variable indexing follows Scout's driver, so tests and environments
     * without Typesense skip it rather than fail.
     */
    public static function enabled(): bool
    {
        return config('scout.driver') === 'typesense'
            && (bool) config('typesense.summary_embedding_config.enabled', false);
    }

    public static function collectionName(): string
    {
        return config('scout.prefix', '').config('typesense.summary_embedding_config.collection', 'metadata_summary');
    }

    /**
     * The partners whose variables are indexed (typesense.summary_embedding_config.partners);
     * empty means every partner.
     *
     * @return string[]
     */
    public static function partners(): array
    {
        return array_values(array_filter(array_map(
            fn (string $partner) => strtoupper(trim($partner)),
            explode(',', (string) config('typesense.summary_embedding_config.partners', ''))
        )));
    }

    public static function indexesPartner(string $partner): bool
    {
        $partners = self::partners();

        return $partners === [] || in_array(strtoupper($partner), $partners, true);
    }

    public function schema(): array
    {
        return [
            'name' => self::collectionName(),
            'fields' => [
                ['name' => 'dataset_id', 'type' => 'string', 'facet' => true],
                ['name' => 'datasetTitle', 'type' => 'string', 'optional' => true],
                ['name' => 'publisherName', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'publisherGatewayId', 'type' => 'string', 'optional' => true],
                ['name' => 'partnerContext', 'type' => 'string', 'optional' => true],
                ['name' => 'datasetType', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                ['name' => 'datasetSubType', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                ['name' => 'populationSize', 'type' => 'int32', 'optional' => true],
                ['name' => 'inPipeline', 'type' => 'string', 'optional' => true, 'facet' => true],

                ['name' => 'field',              'type' => 'string',   'facet' => true],
                ['name' => 'text',               'type' => 'string'],
                
                [
                    'name' => 'embedding',
                    'type' => 'float[]',
                    'embed' => [
                        'from' => ['text'],
                        'model_config' => ['model_name' => config('typesense.summary_embedding_config.model')],
                    ],
                ],
            ],
        ];
    }

    /**
     * Drops and recreates the collection, then indexes every eligible
     * dataset of the indexed partners (partners()). The first create
     * downloads the embedding model, which can take a minute or two.
     *
     * With $resume the collection is kept and datasets whose variables are
     * all indexed already are skipped, so an interrupted rebuild carries on
     * rather than embedding everything again. Variables of partners no
     * longer indexed are removed. A dataset that fails is reported and the
     * rebuild moves on; resuming retries it.
     *
     * $progress receives an event name and its context:
     *   dropping, creating                            the collection
     *   resuming   [indexed]                          datasets already complete
     *   pruned     [variables]                        removed, partner not indexed
     *   started    [datasets]                         eligible dataset versions
     *   dataset    [dataset_id, title, variables]     about to import
     *   batch      [dataset_id, done, variables]      after each import request
     *   indexed    [dataset_id, variables, failed]    dataset finished
     *   skipped    [dataset_id, reason]               not searchable, no variables, already indexed
     *   failed     [dataset_id, error]                dataset abandoned
     *
     * @param  callable(string $event, array $context): void|null  $progress
     */
    public function rebuild(?callable $progress = null, bool $resume = false): int
    {
        $progress ??= fn () => null;
        // $progress = fn () => null;
        $name = self::collectionName();
        $partners = self::partners();

        $alreadyIndexed = [];
        if ($resume && $this->typesense->collectionExists($name)) {
            if ($partners !== []) {
                $pruned = $this->writer()->deleteDocumentsWhere($name, 'partnerContext:!='.self::filterList($partners));
                $progress('pruned', ['documents' => (int) ($pruned['num_deleted'] ?? 0)]);
            }
            $alreadyIndexed = $this->indexedCounts();
            $progress('resuming', ['indexed' => count($alreadyIndexed)]);
        } else {
            if ($this->typesense->collectionExists($name)) {
                $progress('dropping', []);
                $this->typesense->dropCollection($name);
            }
            $progress('creating', []);
            $this->typesense->createCollection($this->schema());
        }

        $eligible = DatasetVersion::query()->indexEligible();
        if ($partners !== []) {
            $eligible->whereHas('dataset', fn ($q) => $q->whereIn('partner_context', $partners));
        }
        $progress('started', ['datasets' => (clone $eligible)->count()]);

        $total = 0;
        $eligible
            ->with(self::documentRelations())
            ->chunkById(100, function ($versions) use (&$total, $progress, $alreadyIndexed) {
                foreach ($versions as $version) {
                    $total += $this->rebuildDataset($version, $progress, $alreadyIndexed);
                }
            });

        return $total;
    }

    /**
     * One dataset's part of rebuild(); returns the variables it indexed.
     *
     * @param  array<int, int>  $alreadyIndexed  variables indexed, by dataset id
     */
    private function rebuildDataset(DatasetVersion $version, callable $progress, array $alreadyIndexed): int
    {
        $datasetId = (int) $version->dataset_id;

        try {
            if (! $version->shouldBeSearchable()) {
                $progress('skipped', ['dataset_id' => $datasetId, 'reason' => 'not searchable']);

                return 0;
            }

            $documents = $this->documentsFor($version);
            if ($documents === []) {
                $progress('skipped', ['dataset_id' => $datasetId, 'reason' => 'no summary metadata']);

                return 0;
            }

            $indexed = $alreadyIndexed[$datasetId] ?? 0;
            if ($indexed === count($documents)) {
                $progress('skipped', ['dataset_id' => $datasetId, 'reason' => 'already indexed']);

                return 0;
            }
            // Partly indexed by an interrupted run: start that dataset afresh.
            if ($indexed > 0) {
                $this->removeDataset($datasetId);
            }

            $progress('dataset', [
                'dataset_id' => $datasetId,
                'title' => $documents[0]['datasetTitle'],
                'documents' => count($documents),
            ]);

            $count = $this->import($documents, fn (int $done) => $progress('batch', [
                'dataset_id' => $datasetId,
                'done' => $done,
                'documents' => count($documents),
            ]));

            $progress('indexed', [
                'dataset_id' => $datasetId,
                'documents' => $count,
                'failed' => count($documents) - $count,
            ]);

            return $count;
        } catch (\Throwable $e) {
            Log::warning('MetadataSummaryIndex: dataset failed to index', [
                'dataset_id' => $datasetId,
                'error' => $e->getMessage(),
            ]);
            $progress('failed', ['dataset_id' => $datasetId, 'error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Variables already in the collection, by dataset id.
     *
     * @return array<int, int>
     */
    private function indexedCounts(): array
    {
        $result = $this->writer()->rawSearch(self::collectionName(), '*', [
            'facet_by' => 'dataset_id',
            // Every dataset, not Typesense's default of the top 10.
            'max_facet_values' => 1000000,
            'per_page' => 0,
        ]);

        $counts = [];
        foreach ($result['facet_counts'][0]['counts'] ?? [] as $facet) {
            $counts[(int) $facet['value']] = (int) $facet['count'];
        }

        return $counts;
    }

    /** A Typesense filter_by value list: [`PRUK`,`CRUK`]. */
    private static function filterList(array $values): string
    {
        return '['.implode(',', array_map(fn (string $v) => '`'.str_replace('`', '', $v).'`', $values)).']';
    }

    /**
     * Replaces one dataset's variables with those of its indexed version,
     * or removes them when no version is indexed or its partner isn't.
     */
    // public function syncDataset(int $datasetId): int
    // {
    //     $this->ensureCollection();
    //     $this->removeDataset($datasetId);

    //     // Read directly: the dataset() relation doesn't select partner_context.
    //     $partner = Dataset::where('id', $datasetId)->value('partner_context');
    //     if ($partner === null || ! self::indexesPartner($partner)) {
    //         return 0;
    //     }

    //     $version = DatasetVersion::query()
    //         ->where('dataset_id', $datasetId)
    //         ->with(self::documentRelations())
    //         ->orderByDesc('version')
    //         ->first();

    //     if (! $version || ! $version->shouldBeSearchable()) {
    //         return 0;
    //     }

    //     return $this->import($this->documentsFor($version));
    // }

    /**
     * What documentsFor() reads from a version's dataset. Eager-loaded
     * because the dataset() relation alone doesn't select partner_context,
     * and every variable would be filed under HDRUK.
     */
    private static function documentRelations(): array
    {
        return ['dataset' => fn ($q) => $q->select(['id', 'status', 'team_id', 'partner_context'])->with('team:id,name')];
    }

    public function removeDataset(int $datasetId): void
    {
        $this->writer()->deleteDocumentsWhere(self::collectionName(), 'dataset_id:='.$datasetId);
    }

    /**
     * The summary variable documents for a dataset version, read from the
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

        $fields = app(GwdmHandlerFactory::class)->resolve($envelope['gwdmVersion'] ?? $version->gwdm_version ?? '2.0')
        ->toSearchableFields($envelope);

        $base = [            
            'dataset_id' => (string) $version->dataset_id,
            'datasetTitle' => (string) ($version->title ?: data_get($envelope, 'metadata.summary.title', '')),
            'publisherName' => (string) ($fields['publisherName'] ?? ''),
            'publisherGatewayId' => (string) data_get($envelope, 'metadata.summary.publisher.gatewayId', ''),
            'partnerContext' => (string) ($version->dataset?->partner_context ?? 'HDRUK'), 
            'datasetType' => array_values((array) ($fields['dataType'] ?? [])),
            'datasetSubType' => array_values((array) ($fields['dataSubType'] ?? [])),
            'populationSize' => (int) ($fields['populationSize'] ?? -1),
            'inPipeline' => (string) data_get($envelope, 'metadata.summary.inPipeline', ''),
        ];

        $documents = [];
        foreach ($this->fieldTexts($version, $envelope, $fields) as $field => $text) {
            $documents[] = $base + [
                'id'    => "{$version->dataset_id}-{$field}",
                'field' => $field,
                'text'  => $text,
            ];
        }

        return $documents;
    }

    private function fieldTexts(DatasetVersion $version, array $envelope, array $fields): array {
        $title = $this->clean((string) ($version->title ?: data_get($envelope, 'metadata.summary.title', '')));
        $aliases = data_get($envelope, 'metadata.summary.datasetAliases') ?? [];

        return array_filter([
                'title' => $title,
                'keywords' => $this->listText((array) ($fields['keywords'] ?? [])),
                'aliases' => $this->listText((array) $aliases), // check with Loki
                'abstract' => $this->clean((string) ($fields['abstract'] ?? '')),
                'description' => $this->clean((string) ($fields['description'] ?? '')),
            ], fn (string $text) => $text !== '');
    }

    /**
     * Avoid repeats that are different only in capitals.
     *
     * @param  array<mixed>  $items
     */
    private function listText(array $items): string {
        $unique = [];
        foreach ($items as $item) {
            $item = $this->clean((string) $item);
            $key = mb_strtolower($item);
            if ($item !== '' && ! isset($unique[$key])) {
                $unique[$key] = $item;
            }
        }
        return implode(', ', $unique);
    }

    private function clean(string $text): string {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
        // remove email addresses and markdown symbols
        $text = preg_replace('/[\w.+-]+@[\w-]+(\.[\w-]+)+/u', '[email removed]', $text) ?? '';
        $text = preg_replace('/[*_#`>|]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }






    /**
     * Upserts in batches of typesense.summary_embedding_config.import_batch_size, so no one
     * request runs long while Typesense embeds it.
     *
     * @param  callable(int $done): void|null  $onBatch  variables sent so far
     */
    private function import(array $documents, ?callable $onBatch = null): int
    {
        $batchSize = max(1, (int) config('typesense.summary_embedding_config.import_batch_size', 100));
        $done = 0;
        $failed = 0;
        $firstError = null;

        foreach (array_chunk($documents, $batchSize) as $batch) {
            $results = $this->writer()->importDocuments(self::collectionName(), $batch, ['action' => 'upsert']);

            foreach ($results as $result) {
                if (! ($result['success'] ?? false)) {
                    $failed++;
                    $firstError ??= $result['error'] ?? null;
                }
            }

            $done += count($batch);
            if ($onBatch) {
                $onBatch($done);
            }
        }

        if ($failed > 0) {
            Log::warning('MetadataSummaryIndex: some variables failed to index', [
                'dataset_id' => $documents[0]['dataset_id'],
                'failed' => $failed,
                'first_error' => $firstError,
            ]);
        }

        return count($documents) - $failed;
    }

    /**
     * Writes give up after typesense.summary_embedding_config.import_timeout rather than
     * wait forever on a dropped connection (see TypesenseService::withTimeout).
     */
    private function writer(): TypesenseService
    {
        return $this->writer ??= $this->typesense->withTimeout(
            max(1, (int) config('typesense.summary_embedding_config.import_timeout', 120))
        );
    }

    // private function ensureCollection(): void
    // {
    //     if (! $this->typesense->collectionExists(self::collectionName())) {
    //         $this->typesense->createCollection($this->schema());
    //     }
    // }
}
