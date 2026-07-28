<?php

namespace App\Jobs;

use Auditor;
use Exception;
use App\SearchProviders\HDRUK;
use App\Services\TypesenseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

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
     */
    public function __construct(string $entity)
    {
        $this->onQueue('indexing');
        $this->timeout = config('jobs.default_timeout', 600);
        $this->tries = config('jobs.ntries', 2);
        $this->entity = $entity;
    }

    public function handle(TypesenseService $typesense): void
    {
        try {
            // A full drop+reimport pulls every eligible row (plus its eager-loaded
            // relations) through toSearchableArray() in chunks, which for
            // heavier entities (e.g. DatasetVersion's JSON metadata blobs) can
            // exceed a default CLI memory_limit long before hitting any table
            // large enough to warrant a permanent php.ini change.
            ini_set('memory_limit', config('jobs.reindex_memory_limit', '512M'));

            $modelClass = HDRUK::typesenseModelMap()[$this->entity] ?? null;

            if ($modelClass === null) {
                throw new Exception("Unknown Typesense entity '{$this->entity}'");
            }

            $collectionName = (new $modelClass())->searchableAs();

            if ($typesense->collectionExists($collectionName)) {
                $typesense->dropCollection($collectionName);
            }

            $typesense->createCollectionFromModel($modelClass);

            Artisan::call('scout:import', ['model' => $modelClass]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
