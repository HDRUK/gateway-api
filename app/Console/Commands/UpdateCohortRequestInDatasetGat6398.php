<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use Illuminate\Console\Command;

class UpdateCohortRequestInDatasetGat6398 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-cohort-request-in-dataset-gat6398';

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
        $array = [23, 1009, 134, 210, 413, 711, 699, 119, 710, 736, 714, 74, 85, 64, 713];

        Dataset::whereIn('id', $array)->update(['is_cohort_discovery' => 1]);

        $this->info('Done');
    }
}
