<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use MetadataManagementController as MMC;

class PhysicalSamplePostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:physical-sample-post-migration {sleep?}';

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
        $this->csvData = $this->readMigrationFile(storage_path() . '/migration_files/datasets_physical_samples_cleaned.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sleep = $this->argument("sleep");
        $sleepTimeInMicroseconds = $sleep !== null ? floatval($sleep) * 1000 * 1000 : null;

        $progressbar = $this->output->createProgressBar(count($this->csvData));
        $progressbar->start();

        foreach ($this->csvData as $csv) {
            $mongoPid = $csv['mongo_pid'];
            $samples = $csv['physical_samples'];

            $samplesList = explode(";", $samples);

            $formattedSamplesArray = [];
            foreach ($samplesList as $sample) {
                $formattedSamplesArray[] = ['materialType' => $sample];
            }

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
                        $metadata['metadata']['tissuesSampleCollection'] = $formattedSamplesArray;
                    }

                    DatasetVersion::where('id', $datasetVersion->id)->update([
                        'metadata' => json_encode(json_encode($metadata)),
                    ]);
                }

                if ($sleepTimeInMicroseconds !== null) {
                    MMC::reindexElastic($dataset->id);
                    usleep($sleepTimeInMicroseconds);
                }
            }
            $progressbar->advance();
        }

        $progressbar->finish();
    }

    private function readMigrationFile(string $migrationFile): array
    {
        $response = [];
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
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
