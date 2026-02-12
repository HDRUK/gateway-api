<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessFederation;
use App\Services\GatewayMetadataIngestionService;

class GatewayMetadataIngestion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:gateway-metadata-ingestion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the gateway metadata ingestion process';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting gateway metadata ingestion...');
        $gmi = new GatewayMetadataIngestionService();

        // First pull all active federations
        $federations = $gmi->getActiveFederations();
        foreach ($federations as $fed) {
            Log::info("Processing federation: {$fed['endpoint_baseurl']} (ID: {$fed['id']})");
            // Spawn a job to handle each active federation
            // within this timeframe. Unblocks a potential timelock
            // if there are many federations and we suffer latency,
            // forcing a federation to wait for the next cycle
            // (which could be a whole day).
            ProcessFederation::dispatch($fed);
        }

        Log::info('Gateway metadata ingestion completed successfully.');

        unset($gmi);
        return 0;
    }
}
