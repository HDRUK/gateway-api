<?php

namespace App\Console\Commands;

use Exception;
use App\Models\CommandConfig;

use Illuminate\Console\Command;

class SiteScraper extends Command
{
    private $ident = 'site_scraper';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:site-scraper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scheduled command to scrap content from a site list';

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
                        var_dump('found auth step');
                        break;
                    case 'run':
                        var_dump('found run step');
                        break;
                    default:
                        throw new Exception('unknown config step ' . $step->type);
                }
            }
        }
    }

    private function authStep(stdClass $authStep) {
        
    }
}
