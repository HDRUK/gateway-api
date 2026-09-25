<?php

namespace App\Jobs;

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
use Illuminate\Support\Str;
use Throwable;

class ProcessFederation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use MetadataVersioning;
    use GatewayMetadataIngestionTrait;

    private readonly Federation $federation;
    private readonly GatewayMetadataIngestionService $gmi;

    /**
     * Minted once here, not derived from queue internals: the previous
     * $this->job?->getJobId() wasn't guaranteed stable across retries, and
     * $this->job->uuid() (used in failed()) was a different identifier
     * again. A constructor property survives every retry unchanged, since
     * Laravel re-runs handle() against the same serialized job payload.
     */
    public readonly string $jobUuid;

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
        $this->jobUuid = (string) Str::uuid();
        $this->onQueue('federation');
    }

    /**
     * Execute the job.
     */
    public function handle(GwdmMetadataHandler $handler): void
    {
        $attempts = $this->attempts();

        // Claim is_running only on the first attempt. On a Laravel-driven
        // retry (attempts() > 1) it's already true from attempt one — we
        // never clear it between retries (see finaliseFederationRun /
        // failed() below), so re-checking here would make a job wrongly
        // reject its own retry. This also closes the previous race: is_running
        // used to be cleared after every failed attempt, leaving a window
        // where a second, unrelated dispatch (e.g. runNow()) could start
        // processing the same federation concurrently while a retry was
        // still pending.
        if ($attempts === 1) {
            $claimed = Federation::where('id', $this->federation->id)
                ->where('is_running', false)
                ->update(['is_running' => true]);

            if ($claimed === 0) {
                $this->log('info', "federation {$this->federation->id} is already running - skipping this attempt");
                return;
            }
        }

        // Here and not in constructor because this library makes excessive use
        // of closures which can't be serialised by Laravel cache.
        $gsms = app(GoogleSecretManagerService::class);
        $remoteItems = $this->pullCatalogueList($this->federation, $gsms);

        if ($remoteItems->isEmpty()) {
            $this->log('warning', 'REMOTE catalogue returned empty "items" array - aborting');
            $this->finaliseFederationRun($this->federation->id, $this->jobUuid);
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
            $gsms,
            $this->gmi,
            $this->jobUuid,
            $attempts,
            $handler,
        );

        $updated = $this->updateLocalDatasetsChangedInRemoteCatalogue(
            $localItems,
            $remoteItems,
            $this->federation,
            $gsms,
            $this->gmi,
            $this->jobUuid,
            $attempts
        );

        $archived = $this->archiveLocalDatasetsNotInRemoteCatalogue($localItems, $remoteItems, $this->gmi, $this->federation, $this->jobUuid, $attempts);

        $this->log('info', "metadata ingestion completed for team {$this->gmi->getTeam()} - created: {$created}, updated: {$updated}, archived: {$archived}");

        $this->finaliseFederationRun($this->federation->id, $this->jobUuid);
    }

    public function failed(Throwable $exception): void
    {
        \Log::error('ProcessFederationJob failed', [
            'federation_id' => $this->federation->id,
            'attempt'       => $this->attempts(),
            'error'         => $exception->getMessage(),
        ]);

        if ($this->attempts() >= $this->tries) {
            // ProcessFederationFailure (listening for this event) clears
            // is_running — retries are exhausted, so there's no pending
            // attempt left that still needs it held.
            FederationProcessingFailed::dispatch($this->federation, $exception, $this->jobUuid);
        }
    }
}
