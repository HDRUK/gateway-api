<?php

namespace App\Jobs;

use App\Services\Search\VariableIndex;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Brings a dataset's variables in the variable search index in line with
 * its indexed version. A failure is logged rather than retried: the next
 * save, or `typesense:reindex variables`, puts it right.
 */
class SyncDatasetVariables implements ShouldQueue
{
    use Queueable;

    public $timeout = 120;

    public function __construct(private readonly int $datasetId) {}

    public function handle(VariableIndex $index): void
    {
        if (! VariableIndex::enabled()) {
            return;
        }

        try {
            $index->syncDataset($this->datasetId);
        } catch (\Throwable $e) {
            Log::warning('SyncDatasetVariables failed', [
                'dataset_id' => $this->datasetId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
