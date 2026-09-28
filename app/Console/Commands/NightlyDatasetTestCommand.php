<?php

namespace App\Console\Commands;

use App\Jobs\NightlyDatasetTestJob;
use Illuminate\Console\Command;

class NightlyDatasetTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:nightly-dataset-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nightly process to check every active dataset\'s page is reachable and record the result.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        NightlyDatasetTestJob::dispatch();
    }
}
