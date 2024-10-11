<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DatasetVersion;

class DeleteOldMetadataVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-old-metadata-versions';

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
        $datasetIds = DataetVersion::select('dataset_id')
            ->groupBy('dataset_id')
            ->havingRaw('MAX(version) > version')
            ->pluck('dataset_id');

        $temp = DatasetVersion::whereIn('dataset_id', $datasetIds)->count();
        echo $temp . "\n";
    }

}
