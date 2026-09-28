<?php

namespace App\Console\Commands;

use App\Jobs\NightlyDatasetLinkCheckJob;
use Illuminate\Console\Command;

class NightlyDatasetLinkCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:nightly-dataset-link-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nightly process to check every active dataset\'s metadata for dead links and email team admins a report.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        NightlyDatasetLinkCheckJob::dispatch();
    }
}
