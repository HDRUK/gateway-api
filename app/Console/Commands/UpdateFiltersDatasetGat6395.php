<?php

namespace App\Console\Commands;

use App\Models\Filter;
use Illuminate\Console\Command;

class UpdateFiltersDatasetGat6395 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-filters-dataset-gat6395';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new filter to Datasets table for Cohort Discovery';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Filter::updateOrCreate([
            'type' => 'dataset',
            'keys' => 'isCohortDiscovery',
        ], [
            'type' => 'dataset',
            'keys' => 'isCohortDiscovery',
        ]);
    }
}
