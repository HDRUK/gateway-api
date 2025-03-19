<?php

namespace App\Console\Commands;

use App\Models\Filter;
use Illuminate\Console\Command;

class UpdateFiltersDatasetGat6396 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-filters-dataset-gat6396';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new filter to Datasets table for Data Standard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Filter::updateOrCreate([
            'type' => 'dataset',
            'keys' => 'formatAndStandards',
        ], [
            'type' => 'dataset',
            'keys' => 'formatAndStandards',
        ]);
    }
}
