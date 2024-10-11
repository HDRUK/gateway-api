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
        $toBeDeleted = DatasetVersion::where('dataset_id', 100)
        ->where('version', '<', function ($query) {
            $query->selectRaw('MAX(version)')
                ->from('dataset_versions as dv')
                ->whereColumn('dv.dataset_id', 'dataset_id');
        })->count();

        $versions = DatasetVersion::where('dataset_id', 100)->count();

        // Output the records that would be deleted
        echo $toBeDeleted . " " . $versions . "\n";
    }

}
