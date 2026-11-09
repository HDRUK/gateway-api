<?php

namespace App\Jobs;

use Auditor;
use Exception;
use App\Exceptions\TypesenseReindexException;
use App\SearchProviders\HDRUK;
use App\Services\Search\CollectionReindexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReindexTypesenseEntity implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries;
    public $timeout;

    protected string $entity;

    /**
     * @param string $entity  A key of HDRUK::typesenseModelMap(), e.g. 'datasets', 'dur'.
     * @param bool   $upsert  Upsert into the live collection instead of rebuilding behind the alias.
     */
    public function __construct(string $entity, public readonly bool $upsert = false)
    {
        $this->onQueue('indexing');
        $this->timeout = config('jobs.default_timeout', 600);
        $this->tries = config('jobs.ntries', 2);
        $this->entity = $entity;
    }

    public function handle(CollectionReindexer $reindexer): void
    {
        try {
            // A full reimport pulls every eligible row (plus its eager-loaded
            // relations) through toSearchableArray() in chunks, which for
            // heavier entities (e.g. DatasetVersion's JSON metadata blobs) can
            // exceed a default CLI memory_limit long before hitting any table
            // large enough to warrant a permanent php.ini change.
            ini_set('memory_limit', config('jobs.reindex_memory_limit', '512M'));

            $modelClass = HDRUK::typesenseModelMap()[$this->entity] ?? null;

            if ($modelClass === null) {
                throw new Exception("Unknown Typesense entity '{$this->entity}'");
            }

            // Search keeps serving the live collection throughout; a failure
            // leaves it as it was.
            $this->upsert
                ? $reindexer->refresh($modelClass)
                : $reindexer->rebuild($modelClass);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            // Rejected documents, schema drift or another run holding the
            // lock: retrying gives the same result, so fail outright.
            if ($e instanceof TypesenseReindexException) {
                $this->fail($e);
                return;
            }

            throw $e;
        }
    }
}
