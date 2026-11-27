<?php

namespace App\Jobs;

use App\Services\Search\MetadataSummaryIndex;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Brings a dataset's summary documents in the metadata summary index in line with
 * its indexed version. A failure is logged rather than retried: the next
 * save, or `typesense:reindex metadata_summary`, puts it right.
 */
class SyncDatasetMetadataSummary implements ShouldQueue
{
    use Queueable;

    public $timeout = 120;

    public function __construct(private readonly int $datasetId)
    {
    }

    public function handle(MetadataSummaryIndex $index): void
    {
        if (! MetadataSummaryIndex::enabled()) {
            return;
        }

        try {
            $index->syncDataset($this->datasetId);
        } catch (\Throwable $e) {
            Log::warning('SyncDatasetMetadataSummary failed', [
                'dataset_id' => $this->datasetId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
