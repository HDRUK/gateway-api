<?php

namespace App\Console\Commands;

use App\Models\Filter;
use Illuminate\Console\Command;

class UpdateFiltersForDataCustodianNetwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-filters-for-data-custodian-network';

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
        Filter::updateOrCreate([
            'type' => 'datacustodiannetwork',
            'keys' => 'datasetTitles',
        ]);
        Filter::updateOrCreate([
            'type' => 'datacustodiannetwork',
            'keys' => 'publisherNames',
        ]);
    }
}
