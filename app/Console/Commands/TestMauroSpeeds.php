<?php

namespace App\Console\Commands;

use Mauro;
use Exception;

use App\Exceptions\MauroServiceException;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestMauroSpeeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-mauro-speeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $then = microtime(true);

        $datasetId = '6f06ebc3-9b79-4230-a19a-aadfdbf90237';

        $url = env('MAURO_API_URL');
        $url .= '/dataModels/' . $datasetId . '/metadata?max=1000';

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
                ->acceptJson()
                ->get($url);

            $now = microtime(true);
            var_dump($now-$then);

            dd($response->json());
        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }
}
