<?php

namespace App\Console\Commands;

use App\Models\Filter;
use Illuminate\Console\Command;

class UpdateDataSubTypeFilter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-datasubtype-filter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the filter entry `datasetSubType` to `dataSubType';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $getFilter = Filter::where(['keys' => 'datasetSubType', 'type' => 'dataset'])->first();

        if (is_null($getFilter)) {
            $this->warn('Filter dataset::datasetSubType not found');
            return;
        }

        $getFilter->keys = 'dataSubType';

        $getFilter->save();
    }
}
