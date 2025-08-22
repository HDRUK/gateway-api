<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Federation;
use App\Http\Traits\MetadataVersioning;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;

use App\Traits\GatewayMetadataIngestionTrait;

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

    /**
     * Create a new job instance.
     */
    public function __construct(Federation $federation)
    {
        $this->federation = $federation;
        $this->gmi = new GatewayMetadataIngestionService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Here and not in constructor because this library makes excessive use
        // of closures which can't be serialised by Laravel cache.
        $this->gsms = new GoogleSecretManagerService();
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

        $this->deleteLocalDatasetsNotInRemoteCatalogue($localItems, $remoteItems);
        $this->createLocalDatasetsMissingFromRemoteCatalogue(
            $localItems,
            $remoteItems,
            $this->federation,
            $this->gsms,
            $this->gmi
        );
        $this->updateLocalDatasetsChangedInRemoteCatalogue(
            $localItems,
            $remoteItems,
            $this->federation,
            $this->gsms,
            $this->gmi
        );

        $this->log('info', "federation processing completed: team_id={$this->gmi->getTeam()}, fed_id={$this->federation->id},
            datasets_created={$this->created}, datasets_deleted={$this->deleted}, datasets_updated={$this->updated}");

        return;
    }
}
