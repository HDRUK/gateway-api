<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use MetadataManagementController AS MMC;

class PhysicalSamplePostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:physical-sample-post-migration {reindex?}';

    private $csvData = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to post-process migrated datasets from mk1 mongo db. Update tissuesSampleCollection with values from file by pid.';

    public function __construct()
    {
        parent::__construct();
        $this->csvData = $this->readMigrationFile(storage_path() . '/migration_files/datasets_physical_samples.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reindex = $this->argument('reindex');
        $reindexEnabled = $reindex !== null;

        $progressbar = $this->output->createProgressBar(count($this->csvData));
        $progressbar->start();

        foreach ($this->csvData as $csv) {
            $mongoPid = $csv['mongo_pid'];
            $samples = $csv['physical_samples'];
            $samplesList = str_replace(";", ",", $samples);
            $dataset = Dataset::where([
                'mongo_pid' => $mongoPid,
            ])->first();

            if ($dataset) {
                $datasetVersion = DatasetVersion::where([
                    'id' => $dataset->id,
                ])->first();

                if ($datasetVersion) {
                    $metadata = $datasetVersion->metadata;

                    if (array_key_exists('tissuesSampleCollection', $metadata['metadata'])) {
                        if (is_null($metadata['metadata']['tissuesSampleCollection'])) {
                            $metadata['metadata']['tissuesSampleCollection'] = [['materialType' => $samplesList]];
                        } else {
                            $metadata['metadata']['tissuesSampleCollection'][0]['materialType'] = $samplesList;
                        }
                    }

                    DatasetVersion::where('id', $dataset->id)->update([
                        'metadata' => json_encode(json_encode($metadata)),
                    ]);
                }

                if ($reindexEnabled) {
                    MMC::reindexElastic($dataset->id);
                }                
            }
            $progressbar->advance();
            sleep(1);
        }

        $progressbar->finish();
    }

    private function readMigrationFile(string $migrationFile): array
    {
        $response = [];
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== FALSE) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[$headers[$key]] = $value ?: '';
            }

            $response[] = $item;
        }

        fclose($file);
        
        return $response;
    }
}
