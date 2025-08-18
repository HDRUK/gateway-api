<?php

namespace App\Jobs;

use Http;
use Config;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
// Specifically removing this as self-instantiated classes cannot be
// serialised. See LS.
// use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Models\Team;
use App\Models\Dataset;
use App\Models\Federation;
use App\Models\DatasetVersion;
use App\Http\Traits\MetadataVersioning;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;

use App\Traits\GatewayMetadataIngestionTrait;

class TestFederation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    // Specifically removing this as self-instantiated classes cannot be
    // serialised. See LS.
    // use SerializesModels;
    use MetadataVersioning;
    use GatewayMetadataIngestionTrait;

    private ?Federation $federation = null;
    private ?GoogleSecretManagerService $gsms = null;
    private ?GatewayMetadataIngestionService $gmi = null;
    private array $authHeaders = [];

    public function __construct(array $input)
    {
        $fed = new Federation([
            'auth_type' => $input['auth_type'],
            'auth_secret_key' => $input['auth_secret_key'],
            'endpoint_baseurl' => $input['endpoint_baseurl'],
            'endpoint_datasets' => $input['endpoint_datasets'],
            'endpoint_dataset' => $input['endpoint_dataset'],
            'run_time_hour' => $input['run_time_hour'],
        ]);

        // Fooling Laravel into thinking this came from DB.
        $fed->exists = true;

        $this->federation = $fed;
        $this->gmi = new GatewayMetadataIngestionService();
    }

    public function handle(): array
    {
        $this->gsms = new GoogleSecretManagerService();
        
        $testCall = $this->pullCatalogueList($this->federation->toArray(), $this->gsms);
        if (is_array($testCall)) {
            // Failure
            return $testCall;
        }

        return [
            'data' => [
                'errors' => '',
                'status' => 200,
                'success' => true,
                'title' => 'Test Successful',
            ],
        ];
    }
}