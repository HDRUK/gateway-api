<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Filter;

class UpdateFiltersDatasetGat5632 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-filters-dataset-gat5632';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renames containsTissue filter to containsBioSamples';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Filter::updateOrCreate([
            'type' => 'dataset',
            'keys' => 'containsTissue',
        ], [
            'type' => 'dataset',
            'keys' => 'containsBioSamples',
        ]);
    }
}
