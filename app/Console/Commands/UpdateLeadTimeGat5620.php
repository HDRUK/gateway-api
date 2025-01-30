<?php

namespace App\Console\Commands;

use Schema;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

class UpdateLeadTimeGat5620 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-lead-time-gat5620 ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GAT-5620 :: Lead time metadata did not pull through from MK1';

    private $csvData = [];

    public function __construct()
    {
        parent::__construct();
        $this->readMigrationFile(storage_path() . '/migration_files/delivery_lead_time_mk1_mappings.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // if (Schema::hasColumn('dataset_versions', 'metadata_backup')) {
        //     $this->info('coloana exista');
        //     // Schema::table('dataset_versions', function (Blueprint $table) {
        //     //     $table->dropColumn('metadata_backup');
        //     // });
        // } else {
        //     $this->warn('coloana no exista');
        //     Schema::table('dataset_versions', function (Blueprint $table) {
        //         $table->json('metadata_backup'); // Modify the column type as needed
        //     });
        // }

        // $this->info('The "dataset_versions.metadata_backup" column has been created.');
        // \DB::update('UPDATE dataset_versions SET metadata_backup = ?', [json_encode([])]);

        // $this->info('A backup is created for the "dataset_versions.metadata" column in the "dataset_versions.metadata_backup" column.');
        // $datasetVersionIds = DatasetVersion::select('id')->get();
        // foreach ($datasetVersionIds as $datasetVersionId) {
        //     $metadata = DatasetVersion::where('id', $datasetVersionId)->select('metadata')->first();
        //     \DB::statement('UPDATE dataset_versions SET metadata_backup = ?', [$metadata]);
        // }
        // $this->info("The backup was created successfully.");

        $this->updateMetadata();
        echo 'Completed ...' . PHP_EOL;
    }

    public function updateMetadata()
    {
        $total = count($this->csvData);
        $notFound = 0;
        foreach ($this->csvData as $item) {
            $datasetMongoId = trim($item['MK1 Mongo Object id']);
            $leadTime = trim($item['Update Value']);

            $dataset = Dataset::where('mongo_object_id', $datasetMongoId)->select('id')->first();

            if (is_null($dataset)) {
                $this->warn('dataset mongo id: ' . $datasetMongoId . ', lead time: ' . $leadTime);
                $notFound++;
                continue;
            }

            $latestDatasetVersionId = Dataset::where('mongo_object_id', $datasetMongoId)->select('id')->first()->latestVersion()->id;
            $metadata = \DB::table('dataset_versions')
                ->where('id', $latestDatasetVersionId)
                ->select('metadata', \DB::raw('JSON_TYPE(metadata) as metadata_type'))
                ->first();

            $this->info('dataset mongo id: ' . $datasetMongoId . ', lead time: ' . $leadTime . ', datasetId: ' . $dataset->id);
            $this->info('Type: ' . $metadata->metadata_type);


            print_r(json_decode($metadata->metadata, true)['metadata']);
            break;
        }

        echo 'Total: ' . $total . PHP_EOL;
        echo 'Dataset Found: ' . ($total - $notFound) . PHP_EOL;
        echo 'Dataset NotFound: ' . $notFound . PHP_EOL;
    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[trim($headers[$key], "\xEF\xBB\xBF")] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);
    }
}
