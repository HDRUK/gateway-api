<?php

namespace App\Services\Search\SearchTraits;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\TypesenseService;
use Illuminate\Support\Facades\Log;

/**
 * A Typesense collection of per-dataset documents that Typesense embeds as
 * they are imported (VariableIndex, MetadataSummaryIndex): rebuilt in full,
 * or synced one dataset at a time.
 *
 * Only the dataset's indexed version (DatasetVersion::shouldBeSearchable)
 * contributes, so the documents always match what dataset search shows.
 *
 * The using class provides a promoted `$typesense` (TypesenseService),
 * configKey(), defaultCollectionName(), documentNoun(), schema() and
 * documentsFor().
 */
trait IndexesEmbeddedDocuments
{
    private ?TypesenseService $writer = null;
    abstract public static function collectionName(): string;
    abstract public static function partners(): array;
    abstract public static function indexesPartner(string $partner): bool;

    abstract public static function configKey(): string;

    public static function setting(string $key, mixed $default = null): mixed
    {
        return config(static::configKey().'.'.$key, $default);
    }

    /** The collection's name when the config block has no `collection`. */
    abstract protected static function defaultCollectionName(): string;

    /** What one document is called in logs and progress events. */
    abstract public static function documentNoun(): string;

    /** The Typesense collection schema. */
    abstract public function schema(): array;

    /** The documents to index for one dataset version. */
    abstract public function documentsFor(DatasetVersion $version): array;

    /** Whether the feature is on when the config block has no `enabled`. */


    /** The 'skipped' reason for a version with no documents. */
    protected static function emptyReason(): string
    {
        return 'no '.static::documentNoun();
    }

    /**
     * Indexing follows Scout's driver, so tests and environments without
     * Typesense skip it rather than fail.
     */


    /**
     * Drops and recreates the collection, then indexes every eligible
     * dataset of the indexed partners (partners()). The first create
     * downloads the embedding model, which can take a minute or two.
     *
     * With $resume the collection is kept and datasets whose documents are
     * all indexed already are skipped, so an interrupted rebuild carries on
     * rather than embedding everything again. Documents of partners no
     * longer indexed are removed. A dataset that fails is reported and the
     * rebuild moves on; resuming retries it.
     *
     * $progress receives an event name and its context, where {noun} is
     * documentNoun():
     *   dropping, creating                         the collection
     *   resuming   [indexed]                       datasets already complete
     *   pruned     [{noun}]                        removed, partner not indexed
     *   started    [datasets]                      eligible dataset versions
     *   dataset    [dataset_id, title, {noun}]     about to import
     *   batch      [dataset_id, done, {noun}]      after each import request
     *   indexed    [dataset_id, {noun}, failed]    dataset finished
     *   skipped    [dataset_id, reason]            not searchable, emptyReason(), already indexed
     *   failed     [dataset_id, error]             dataset abandoned
     *
     * @param  callable(string $event, array $context): void|null  $progress
     */
    public function rebuild(?callable $progress = null, bool $resume = false): int
    {
        $progress ??= fn () => null;
        $name = static::collectionName();
        $partners = static::partners();

        $alreadyIndexed = [];
        if ($resume && $this->typesense->collectionExists($name)) {
            if ($partners !== []) {
                $pruned = $this->writer()->deleteDocumentsWhere($name, 'partnerContext:!='.self::filterList($partners));
                $progress('pruned', [static::documentNoun() => (int) ($pruned['num_deleted'] ?? 0)]);
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
     * One dataset's part of rebuild(); returns the documents it indexed.
     *
     * @param  array<int, int>  $alreadyIndexed  documents indexed, by dataset id
     */
    private function rebuildDataset(DatasetVersion $version, callable $progress, array $alreadyIndexed): int
    {
        $datasetId = (int) $version->dataset_id;
        $noun = static::documentNoun();

        try {
            if (! $version->shouldBeSearchable()) {
                $progress('skipped', ['dataset_id' => $datasetId, 'reason' => 'not searchable']);

                return 0;
            }

            $documents = $this->documentsFor($version);
            if ($documents === []) {
                $progress('skipped', ['dataset_id' => $datasetId, 'reason' => static::emptyReason()]);

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
                $noun => count($documents),
            ]);

            $count = $this->import($documents, fn (int $done) => $progress('batch', [
                'dataset_id' => $datasetId,
                'done' => $done,
                $noun => count($documents),
            ]));

            $progress('indexed', [
                'dataset_id' => $datasetId,
                $noun => $count,
                'failed' => count($documents) - $count,
            ]);

            return $count;
        } catch (\Throwable $e) {
            Log::warning(class_basename(static::class).': dataset failed to index', [
                'dataset_id' => $datasetId,
                'error' => $e->getMessage(),
            ]);
            $progress('failed', ['dataset_id' => $datasetId, 'error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Documents already in the collection, by dataset id.
     *
     * @return array<int, int>
     */
    private function indexedCounts(): array
    {
        $result = $this->writer()->rawSearch(static::collectionName(), '*', [
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
     * Replaces one dataset's documents with those of its indexed version,
     * or removes them when no version is indexed or its partner isn't.
     */
    public function syncDataset(int $datasetId): int
    {
        $this->ensureCollection();
        $this->removeDataset($datasetId);

        // Read directly: the dataset() relation doesn't select partner_context.
        $partner = Dataset::where('id', $datasetId)->value('partner_context');
        if ($partner === null || ! static::indexesPartner($partner)) {
            return 0;
        }

        $version = DatasetVersion::query()
            ->where('dataset_id', $datasetId)
            ->with(self::documentRelations())
            ->orderByDesc('version')
            ->first();

        if (! $version || ! $version->shouldBeSearchable()) {
            return 0;
        }

        return $this->import($this->documentsFor($version));
    }

    /**
     * What documentsFor() reads from a version's dataset. Eager-loaded
     * because the dataset() relation alone doesn't select partner_context,
     * and every document would be filed under HDRUK.
     */
    private static function documentRelations(): array
    {
        return ['dataset' => fn ($q) => $q->select(['id', 'status', 'team_id', 'partner_context'])->with('team:id,name')];
    }

    public function removeDataset(int $datasetId): void
    {
        $this->writer()->deleteDocumentsWhere(static::collectionName(), 'dataset_id:='.$datasetId);
    }

    /**
     * Upserts in batches of the config block's `import_batch_size`, so no
     * one request runs long while Typesense embeds it.
     *
     * @param  callable(int $done): void|null  $onBatch  documents sent so far
     */
    private function import(array $documents, ?callable $onBatch = null): int
    {
        $batchSize = max(1, (int) static::setting('import_batch_size', 100));
        $done = 0;
        $failed = 0;
        $firstError = null;

        foreach (array_chunk($documents, $batchSize) as $batch) {
            $results = $this->writer()->importDocuments(static::collectionName(), $batch, ['action' => 'upsert']);

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
            Log::warning(sprintf('%s: some %s failed to index', class_basename(static::class), static::documentNoun()), [
                'dataset_id' => $documents[0]['dataset_id'],
                'failed' => $failed,
                'first_error' => $firstError,
            ]);
        }

        return count($documents) - $failed;
    }

    /**
     * Writes give up after the config block's `import_timeout` rather than
     * wait forever on a dropped connection (see TypesenseService::withTimeout).
     */
    private function writer(): TypesenseService
    {
        return $this->writer ??= $this->typesense->withTimeout(
            max(1, (int) static::setting('import_timeout', 120))
        );
    }

    private function ensureCollection(): void
    {
        if (! $this->typesense->collectionExists(static::collectionName())) {
            $this->typesense->createCollection($this->schema());
        }
    }
}
