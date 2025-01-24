<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;

class RemoveExtraDatasetVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rm-extra-dataset-versions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove extra dataset versions to combat memory issue - preprod v1.4.0';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $allDatasets = Dataset::all()->select('id')->pluck('id');
            foreach($allDatasets as $dataset) {
                $versions = DatasetVersion::where('dataset_id', $dataset)
                    ->orderBy('version', 'desc')
                    ->select('id')
                    ->get()
                    ->toArray();
                if (count($versions) > 1) {
                    $latest = array_shift($versions);
                    foreach ($versions as $v) {
                        DatasetVersion::where('id', $v['id'])->delete();
                    }
                }
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
