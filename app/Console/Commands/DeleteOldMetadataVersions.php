<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        $toBeDeleted = DatasetVersion::whereIn('dataset_id', [100, /* other dataset_ids */])
        ->whereColumn('version', '<', function ($query) {
            $query->selectRaw('MAX(version)')
                ->from('dataset_versions')
                ->whereColumn('dataset_versions.dataset_id', 'dataset_id');
        })
        ->get();

        // Output the records that would be deleted
        dd($toBeDeleted);
    }

}
