<?php

namespace App\Jobs;

use Auditor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Traits\LoggingContext;
use App\Models\DatasetVersion;
use App\Services\Gwdm\GwdmHandlerFactory;

class LinkageExtraction implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use LoggingContext;

    public $tries   = 3;
    public $backoff = 30;

    protected string $sourceDatasetId = ‘’;
    protected string $sourceDatasetVersionId = ‘’;
    protected string $gwdmVersion = ‘’;

    private ?array $loggingContext = null;

    public function __construct(string $datasetId, string $datasetVersionId)
    {
        $this->onQueue(‘enrichment’);
        try {
            $version = DatasetVersion::findOrFail($datasetVersionId);

            $this->sourceDatasetId        = $datasetId;
            $this->sourceDatasetVersionId = $datasetVersionId;
            // Indexed column is reliable on delta rows where the metadata envelope
            // stores only a patch, not the full document.
            $this->gwdmVersion = $version->gwdm_version ?? $version->metadata[‘gwdmVersion’] ?? ‘2.0’;

            $this->loggingContext = $this->getLoggingContext(\request());
            $this->loggingContext[‘method_name’] = class_basename($this);
        } catch (Exception $e) {
            Auditor::log([
                ‘action_type’ => ‘EXCEPTION’,
                ‘action_name’ => __METHOD__,
                ‘description’ => $e->getMessage(),
            ]);

            throw new Exception(‘Error initializing LinkageExtraction job: ‘ . $e->getMessage());
        }
    }

    public function handle(GwdmHandlerFactory $handlerFactory): void
    {
        try {
            $version = DatasetVersion::findOrFail($this->sourceDatasetVersionId);
            $handlerFactory->resolve($this->gwdmVersion)->extractLinkages($version);
        } catch (Exception $e) {
            Auditor::log([
                ‘action_type’ => ‘EXCEPTION’,
                ‘action_name’ => __METHOD__,
                ‘description’ => $e->getMessage(),
            ]);

            \Log::info(‘Error handling LinkageExtraction job: ‘ . $e->getMessage(), $this->loggingContext);

            throw new Exception(‘Error handling LinkageExtraction job: ‘ . $e->getMessage());
        }
    }

    public function tags(): array
    {
        return [
            ‘dataset:’ . $this->sourceDatasetId,
            ‘version:’ . $this->sourceDatasetVersionId,
        ];
    }
}
