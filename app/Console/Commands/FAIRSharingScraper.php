<?php

namespace App\Console\Commands;

use stdClass;
use Exception;
use App\Models\CommandConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FAIRSharingScraper extends Command
{
    private $authToken = null;

    private $ident = 'fs_scraper';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fs-scraper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scheduled command to scrape content from a site list';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $config = CommandConfig::where('enabled', 1)
            ->where('ident', $this->ident)->get();

        // Each SiteScrape entry will come with its own Config, which define
        // the steps for the commmand to take. The steps are in the form of
        // "auth" and "run" currently. Additional steps will need further
        // code to enact the functionality
        foreach ($config as $scrape) {
            $config = json_decode($scrape->config, false);

            foreach ($config->steps as $step) {
                switch ($step->type) {
                    case 'auth':
                        $this->authStep($step);
                        break;
                    case 'run':
                        $this->runStep($step);
                        break;
                    default:
                        throw new Exception('unknown config step ' . $step->type);
                }
            }
        }
    }

    /**
     * Actions the "run" step of the discovered CommandConfig
     *
     * @param stdClass $runStep The object containing the steps to "run"
     */
    private function runStep(stdClass $runStep): void
    {
        $response = null;
        switch (strtolower($runStep->auth_type)) {
            case 'bearer':
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->authToken,
                    ])
                    ->acceptJson()
                    ->{$runStep->method}($runStep->url);
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
                break;
            default:
                throw new Exception('unknown auth type: ' . $runStep->auth_type);
        }

        // TODO - Do something with the response
        dd($response->json());
    }

    /**
     * Actions the "auth" step of the discovered CommandConfig
     *
     * @param stdClass $authStep The object containing the steps to "auth"
     */
    private function authStep(stdClass $authStep): void
    {
        $response = Http::acceptJson()
            ->post($authStep->url, $authStep->payload);

        if ($response->status() === 200) {
            $this->authToken = $response->json()[$authStep->token_response_key];
            return;
        }

        throw new Exception('authStep received non 200 status: ' . $response->json());
    }
}
