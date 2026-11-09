<?php

namespace App\Services\Search;

use App\Exceptions\TypesenseReindexException;
use App\Services\TypesenseService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Reindexes a Scout model's Typesense collection without search going empty.
 *
 * Each model's searchableAs() name is a Typesense alias over a versioned
 * collection ({alias}_{timestamp}). Scout's searches and live writes go
 * through the alias unchanged, because Typesense resolves aliases for every
 * collection and document call.
 *
 *  - rebuild(): builds a new versioned collection from the model's current
 *    schema, catches up on rows edited during the build, swaps the alias,
 *    then drops the old collection. Needed when the schema changes.
 *  - refresh(): upserts every eligible record into the live collection and
 *    deletes documents for records that are gone or no longer searchable.
 *    For routine resyncs when the schema hasn't changed.
 *
 * Both report progress through an optional callback, fn (string $event,
 * array $context), with these events: creating, chunk, imported,
 * caught_up, dropping_legacy, swapped, dropped_previous, swept, pruned,
 * falling_back.
 */
class CollectionReindexer
{
    public function __construct(private readonly TypesenseService $typesense)
    {
    }

    /**
     * @param  class-string  $modelClass  A model using Laravel\Scout\Searchable with a typesenseCollectionSchema().
     * @return array{collection: string, documents: int, previous: ?string}
     */
    public function rebuild(string $modelClass, ?callable $progress = null): array
    {
        $alias = (new $modelClass())->searchableAs();

        return $this->locked($alias, fn () => $this->rebuildUnlocked($modelClass, $alias, $this->progress($progress)));
    }

    /**
     * @param  class-string  $modelClass  A model using Laravel\Scout\Searchable with a typesenseCollectionSchema().
     * @return array{collection: string, documents: int, pruned: int}
     */
    public function refresh(string $modelClass, ?callable $progress = null): array
    {
        $alias = (new $modelClass())->searchableAs();
        $progress = $this->progress($progress);

        return $this->locked($alias, function () use ($modelClass, $alias, $progress) {
            if (! $this->typesense->collectionExists($alias)) {
                $progress('falling_back', ['reason' => "'{$alias}' does not exist yet"]);

                return $this->rebuildUnlocked($modelClass, $alias, $progress) + ['pruned' => 0];
            }

            $this->assertSchemaMatches($modelClass, $alias);

            $ids = $this->import($modelClass, $alias, $progress);
            $progress('imported', ['collection' => $alias, 'documents' => count($ids)]);

            $pruned = $this->prune($modelClass, $alias, $ids);
            $progress('pruned', ['documents' => $pruned]);

            return [
                'collection' => $this->typesense->aliasTarget($alias) ?? $alias,
                'documents'  => count($ids),
                'pruned'     => $pruned,
            ];
        });
    }

    private function rebuildUnlocked(string $modelClass, string $alias, Closure $progress): array
    {
        $model = new $modelClass();
        $started = Carbon::now();
        $target = $alias . '_' . $started->format('YmdHisu');

        // Never reuse a name: the catch below would drop whatever has it.
        if ($this->typesense->collectionExists($target)) {
            throw new TypesenseReindexException("Collection '{$target}' already exists.");
        }

        $schema = $model->typesenseCollectionSchema();
        $schema['name'] = $target;

        $progress('creating', ['collection' => $target]);
        $this->typesense->createCollection($schema);

        try {
            $ids = $this->import($modelClass, $target, $progress);
            $progress('imported', ['collection' => $target, 'documents' => count($ids)]);

            $caughtUp = $this->catchUp($modelClass, $target, $started);
            $progress('caught_up', ['documents' => $caughtUp]);
        } catch (Throwable $e) {
            $this->dropQuietly($target);
            throw $e;
        }

        $previous = $this->typesense->aliasTarget($alias);

        // One-off per environment: an alias can't take the name of a
        // physical collection, so the pre-alias collection has to go first.
        // Search is empty only between these two calls.
        if ($previous === null && $this->typesense->collectionExists($alias)) {
            $progress('dropping_legacy', ['collection' => $alias]);
            $this->typesense->dropCollection($alias);
        }

        $this->typesense->upsertAlias($alias, $target);
        $progress('swapped', ['alias' => $alias, 'collection' => $target]);

        if ($previous !== null && $previous !== $target) {
            $this->dropQuietly($previous);
            $progress('dropped_previous', ['collection' => $previous]);
        }

        $this->sweepOrphans($alias, $target, $progress);

        return [
            'collection' => $target,
            'documents'  => $this->typesense->documentCount($target),
            'previous'   => $previous,
        ];
    }

    /**
     * Upserts every eligible record, chunked the way scout:import does it.
     * Rejected documents are collected and reported together at the end,
     * after the rest have been written.
     *
     * @param  null|Closure(Builder): void  $scope  Narrows the records imported.
     * @return string[] Ids of the documents sent, including any rejected.
     */
    private function import(string $modelClass, string $collection, Closure $progress, ?Closure $scope = null): array
    {
        $query = $modelClass::makeAllSearchableQuery();
        if ($scope !== null) {
            $scope($query);
        }

        $key = (new $modelClass())->getScoutKeyName();
        $ids = [];
        $rejected = [];

        $query->chunkById(config('scout.chunk.searchable', 500), function (Collection $models) use ($collection, $progress, &$ids, &$rejected) {
            $documents = $this->documents($models->filter->shouldBeSearchable()->values());

            if ($documents !== []) {
                $results = $this->typesense->importDocuments($collection, $documents, ['action' => 'upsert']);

                foreach ($results as $i => $result) {
                    if (! ($result['success'] ?? false)) {
                        $rejected[] = "{$documents[$i]['id']}: " . ($result['error'] ?? 'unknown error');
                    }
                }
            }

            array_push($ids, ...array_column($documents, 'id'));
            $progress('chunk', ['collection' => $collection, 'documents' => count($ids)]);
        }, $query->qualifyColumn($key), $key);

        if ($rejected !== []) {
            throw new TypesenseReindexException(sprintf(
                "Typesense rejected %d documents for '%s', e.g. %s",
                count($rejected),
                $collection,
                implode('; ', array_slice($rejected, 0, 3)),
            ));
        }

        return $ids;
    }

    /**
     * The documents Scout's TypesenseEngine::update() would write for these models.
     */
    private function documents(Collection $models): array
    {
        if ($models->isEmpty()) {
            return [];
        }

        $models = $models->first()->makeSearchableUsing($models);

        if (config('scout.soft_delete', false) && in_array(SoftDeletes::class, class_uses_recursive($models->first()))) {
            $models->each->pushSoftDeleteMetadata();
        }

        return $models->map(function ($model) {
            $data = $model->toSearchableArray();
            if (empty($data)) {
                return null;
            }

            $data['id'] = (string) ($data['id'] ?? $model->getScoutKey());

            return array_merge($data, $model->scoutMetadata());
        })->filter()->values()->all();
    }

    /**
     * Scout's live writes during a rebuild land in the old collection via the
     * alias, so re-import rows edited since the build started and remove any
     * that stopped being searchable. Rows hard-deleted mid-build aren't seen
     * here; the next refresh() prunes them.
     */
    private function catchUp(string $modelClass, string $target, Carbon $started): int
    {
        $model = new $modelClass();
        if (! $model->usesTimestamps()) {
            return 0;
        }

        $updatedAt = $model->qualifyColumn($model->getUpdatedAtColumn());
        $changed = fn (Builder $query) => $query->where($updatedAt, '>=', $started);

        $upserted = $this->import($modelClass, $target, $this->progress(null), $changed);

        $touched = $changed($modelClass::query())->pluck($model->getScoutKeyName())->map(fn ($id) => (string) $id)->all();
        $this->typesense->deleteDocumentsByIds($target, array_values(array_diff($touched, $upserted)));

        return count($touched);
    }

    /**
     * Deletes documents in the collection that the import didn't send.
     * Each candidate is checked against the database first: a record created
     * after its chunk was read (and written live by Scout) is upserted and
     * kept, not deleted.
     *
     * @param  string[]  $imported
     */
    private function prune(string $modelClass, string $collection, array $imported): int
    {
        $stale = array_values(array_diff($this->typesense->exportDocumentIds($collection), $imported));

        $pruned = 0;
        foreach (array_chunk($stale, 1000) as $batch) {
            $stillEligible = $this->import(
                $modelClass,
                $collection,
                $this->progress(null),
                fn (Builder $query) => $query->whereKey($batch),
            );

            $pruned += $this->typesense->deleteDocumentsByIds($collection, array_values(array_diff($batch, $stillEligible)));
        }

        return $pruned;
    }

    /**
     * Upserting into a collection whose schema no longer matches the model
     * would reject documents, or index fields the wrong way, so refuse and
     * send the caller to rebuild().
     */
    private function assertSchemaMatches(string $modelClass, string $collection): void
    {
        $expected = $this->fieldSignatures((new $modelClass())->typesenseCollectionSchema()['fields'] ?? []);
        $live = $this->fieldSignatures($this->typesense->getCollection($collection)['fields'] ?? []);

        $differences = [];
        foreach (array_diff_key($expected, $live) as $name => $_) {
            $differences[] = "{$name} missing from the live collection";
        }
        foreach (array_diff_key($live, $expected) as $name => $_) {
            $differences[] = "{$name} no longer in the model's schema";
        }
        foreach (array_intersect_key($expected, $live) as $name => $signature) {
            if ($signature !== $live[$name]) {
                $differences[] = "{$name} changed from {$live[$name]} to {$signature}";
            }
        }

        if ($differences !== []) {
            throw new TypesenseReindexException(sprintf(
                "The live '%s' schema differs from %s::typesenseCollectionSchema() (%s). Run typesense:reindex without --upsert to rebuild it.",
                $collection,
                class_basename($modelClass),
                implode('; ', $differences),
            ));
        }
    }

    /**
     * Name => the settings that change how a field is stored or matched.
     * 'id' is skipped: Typesense handles it outside the field list.
     *
     * @return array<string, string>
     */
    private function fieldSignatures(array $fields): array
    {
        $signatures = [];
        foreach ($fields as $field) {
            if ($field['name'] === 'id') {
                continue;
            }

            $flags = array_keys(array_filter([
                'facet'    => $field['facet'] ?? false,
                'optional' => $field['optional'] ?? false,
                'infix'    => $field['infix'] ?? false,
            ]));

            $signatures[$field['name']] = $field['type'] . ($flags === [] ? '' : ' (' . implode(', ', $flags) . ')');
        }

        return $signatures;
    }

    /**
     * Drops {alias}_{timestamp} collections left by runs that died before
     * cleaning up. Safe while the lock is held: no other run is building one.
     */
    private function sweepOrphans(string $alias, string $current, Closure $progress): void
    {
        $pattern = '/^' . preg_quote($alias, '/') . '_\d{20}$/';

        $orphans = collect($this->typesense->listCollections())
            ->pluck('name')
            ->filter(fn (string $name) => $name !== $current && preg_match($pattern, $name))
            ->values()
            ->all();

        foreach ($orphans as $name) {
            $this->dropQuietly($name);
        }

        if ($orphans !== []) {
            $progress('swept', ['collections' => $orphans]);
        }
    }

    private function dropQuietly(string $collection): void
    {
        try {
            $this->typesense->dropCollection($collection);
        } catch (Throwable) {
            // Already gone, or Typesense unreachable: sweepOrphans() retries next run.
        }
    }

    private function locked(string $alias, Closure $callback): array
    {
        $lock = Cache::lock("typesense-reindex:{$alias}", (int) config('typesense.reindex.lock_seconds', 3600));

        if (! $lock->get()) {
            throw new TypesenseReindexException("A reindex of '{$alias}' is already running.");
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    private function progress(?callable $progress): Closure
    {
        return $progress !== null ? Closure::fromCallable($progress) : fn () => null;
    }
}
