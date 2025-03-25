<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use Illuminate\Console\Command;

class UpdateMetadataToSameFormat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-metadata-to-same-format';

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
        $datasetVersions = DatasetVersion::select('id')->get();

        foreach ($datasetVersions as $datasetVersion) {
            $metadata = \DB::table('dataset_versions')
                ->where('id', $datasetVersion->id)
                ->select('id', 'dataset_id', 'metadata', \DB::raw('JSON_TYPE(metadata) as metadata_type'))
                ->first();

            if ($metadata->metadata_type === 'STRING') {
                $arrayMetadata = json_decode(json_decode($metadata->metadata), true);
                DatasetVersion::where([
                    'id' => $datasetVersion->id,
                ])->update([
                    'metadata' => $arrayMetadata,
                ]);
                $this->info('metadata with id = ' . $datasetVersion->id . ' converted');
                unset($arrayMetadata);
            }
            unset($metadata);
        }
    }
}
