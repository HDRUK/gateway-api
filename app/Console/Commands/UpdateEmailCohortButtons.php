<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailCohortButtons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-cohort-buttons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // cohort.discovery.access.expired
        EmailTemplate::where('identifier', 'cohort.discovery.access.expired')->update([
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "env(GATEWAY_URL)/en/about/cohort-discovery"
                        }
                    ]
                }
            ',
        ]);

        // cohort.discovery.access.will.expire
        EmailTemplate::where('identifier', 'cohort.discovery.access.will.expire')->update([
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "env(GATEWAY_URL)/en/about/cohort-discovery"
                        }
                    ]
                }
            ',
        ]);

        // cohort.discovery.access.approved
        EmailTemplate::where('identifier', 'cohort.discovery.access.approved')->update([
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_ACCESS_URL]]",
                            "actual": "env(GATEWAY_URL)/en/en/about/cohort-discovery"
                        }
                    ]
                }
            ',
        ]);
    }
}
