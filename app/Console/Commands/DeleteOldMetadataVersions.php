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
        DatasetVersion::where('dataset_id', 100)->whereIn(
            'dataset_id',
            function ($query) {
                $query->select('dataset_id')
                    ->from('dataset_versions')
                    ->groupBy('dataset_id')
                    ->havingRaw('version < MAX(version)');
            }
        )->delete();
    }

}
