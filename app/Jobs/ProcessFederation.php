<?php

namespace App\Jobs;

use App\Events\FederationProcessed;
use App\Events\FederationProcessingFailed;
use App\Http\Traits\MetadataVersioning;
use App\Models\Federation;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;
use App\Services\Gwdm\GwdmMetadataHandler;
use App\Traits\GatewayMetadataIngestionTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessFederation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use MetadataVersioning;
    use GatewayMetadataIngestionTrait;

    private ?Federation $federation = null;
    private ?GoogleSecretManagerService $gsms = null;
    private ?GatewayMetadataIngestionService $gmi = null;
    private array $authHeaders = [];

    private int $deleted = 0;
    private int $updated = 0;
    private int $created = 0;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(Federation $federation)
    {
        $this->federation = $federation;
        $this->gmi = new GatewayMetadataIngestionService();
        $this->onQueue('federation');
    }

    /**
     * Execute the job.
     */
    public function handle(GwdmMetadataHandler $handler): void
    {
        $jobUuid = $this->job?->getJobId() ?? null;
        $attempts = $this->attempts();

        $this->federation->update(['is_running' => true]);

        try {
            // Here and not in constructor because this library makes excessive use
            // of closures which can't be serialised by Laravel cache.
            $this->gsms = app(GoogleSecretManagerService::class);
            $remoteItems = $this->pullCatalogueList($this->federation, $this->gsms);

            if ($remoteItems->isEmpty()) {
                $this->log('warning', 'REMOTE catalogue returned empty "items" array - aborting');
                return;
            }

            $this->log('info', 'found items in remote collection ' . json_encode($remoteItems));
            $this->gmi->setTeam($this->federation->team[0]->id);
            $this->log('info', 'setting team context for federation pull ' . $this->gmi->getTeam());

            $localItems = $this->getLocalDatasetsForFederatedTeam($this->gmi);

            $this->log('info', 'retrieved local collection items ' . json_encode($localItems));

            $created = $this->createLocalDatasetsMissingFromRemoteCatalogue(
                $localItems,
                $remoteItems,
                $this->federation,
                $this->gsms,
                $this->gmi,
                $jobUuid,
                $attempts,
                $handler,
            );

            // Refresh our potentially mutated list of local items
            $localItems = $this->getLocalDatasetsForFederatedTeam($this->gmi);

            $updated = $this->updateLocalDatasetsChangedInRemoteCatalogue(
                $localItems,
                $remoteItems,
                $this->federation,
                $this->gsms,
                $this->gmi,
                $jobUuid,
                $attempts
            );

            // Refresh our potentially mutated list of local items
            $localItems = $this->getLocalDatasetsForFederatedTeam($this->gmi);

            $archived = $this->archiveLocalDatasetsNotInRemoteCatalogue($localItems, $remoteItems, $this->gmi, $this->federation, $jobUuid, $attempts);

            $this->log('info', "metadata ingestion completed for team {$this->gmi->getTeam()} - created: {$created}, updated: {$updated}, archived: {$archived}");

            FederationProcessed::dispatch($this->federation, $jobUuid);
        } finally {
            $this->federation->update(['is_running' => false]);
        }
    }

    public function failed(Throwable $exception): void
    {
        \Log::error('ProcessFederationJob failed', [
            'federation_id' => $this->federation->id,
            'attempt'       => $this->attempts(),
            'error'         => $exception->getMessage(),
        ]);

        if ($this->attempts() >= $this->tries) {
            FederationProcessingFailed::dispatch($this->federation, $exception, $this->job->uuid());
        }
    }
}
