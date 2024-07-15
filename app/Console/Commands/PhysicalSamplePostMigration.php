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

            //The following $samplesList should have been cleaned and fixed....
            // - it should be a controlled list
            // - this is taking directly from Mk1 and contains nonsense...
            //GAT-4628 has been creaed for someone to do this
            //See: 
            // - https://github.com/HDRUK/traser-mapping-files/blob/master/maps/HDRUK/2.2.0/HDRUK/2.1.2/translation.jsonata#L10-L24
            // - https://github.com/HDRUK/traser-mapping-files/blob/master/maps/HDRUK/2.2.0/HDRUK/2.1.2/translation.jsonata#L39-L45
            /*
                $allowedMaterialTypes := [
                    "Blood",
                    "DNA",
                    "Faeces",
                    "Immortalized Cell Lines",
                    "Isolated Pathogen",
                    "Other",
                    "Plasma",
                    "RNA",
                    "Saliva",
                    "Serum",
                    "Tissue (Frozen)",
                    "Tissue (FFPE)",
                    "Urine"
                 ];
            */
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

                    DatasetVersion::where('id', $dataset->id)->update([
                        'metadata' => json_encode(json_encode($metadata)),
                    ]);

                }

                if ($reindexEnabled) {
                    MMC::reindexElastic($dataset->id);
                    sleep(1);
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
