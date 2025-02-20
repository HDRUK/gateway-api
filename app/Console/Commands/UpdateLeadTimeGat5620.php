<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use Illuminate\Support\Arr;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UpdateLeadTimeGat5620 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-lead-time-gat5620 {argument}';

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
        $action = $this->argument('argument');
        switch ($action) {
            case 'create_backup':
                $this->info('create backup');
                $this->createBackup();
                break;
            case 'remove_backup':
                $this->info('remove_backup');
                $this->removeBackup();
                break;
            case 'update':
                $this->info('update');
                $this->updateMetadata();
                break;
            case 'undo_from_backup':
                $this->info('undo_from_backup');
                $this->copyFromBackup();
                break;
            default:
                $this->info('no argument');
                return;
        }

        return;

        echo 'Completed ...' . PHP_EOL;
    }

    public function createBackup()
    {
        if (!Schema::hasColumn('dataset_versions', 'metadata_backup')) {
            Schema::table('dataset_versions', function (Blueprint $table) {
                $table->json('metadata_backup');
            });
        }

        $datasetVersionIds = DatasetVersion::select('id')->get();
        foreach ($datasetVersionIds as $datasetVersionId) {
            \DB::table('dataset_versions')->where('id', $datasetVersionId->id)->update(['metadata_backup' => \DB::raw('metadata')]);
        }
    }

    public function removeBackup()
    {
        if (!Schema::hasColumn('dataset_versions', 'metadata_backup')) {
            $this->warn('the "dataset_versions.metadata_backup" column not found!');
            return;
        }

        if (Schema::hasColumn('dataset_versions', 'metadata_backup')) {
            Schema::table('dataset_versions', function (Blueprint $table) {
                $table->dropColumn('metadata_backup');
            });
            $this->warn('the "dataset_versions.metadata_backup" column was removed!');
            return;
        }
    }

    public function copyFromBackup()
    {
        if (!Schema::hasColumn('dataset_versions', 'metadata_backup')) {
            $this->warn('the "dataset_versions.metadata_backup" column not found!');
            return;
        }

        $checking = DatasetVersion::where('metadata_backup', '{}')->first();
        if (!is_null($checking)) {
            $this->warn('no data found in "dataset_versions.metadata_backup"!');
            return;
        }

        $datasetVersionIds = DatasetVersion::select('id')->get();
        foreach ($datasetVersionIds as $datasetVersionId) {
            \DB::table('dataset_versions')->where('id', $datasetVersionId->id)->update(['metadata' => \DB::raw('metadata_backup')]);
        }
    }

    public function updateMetadata()
    {
        $total = count($this->csvData);
        $notFound = 0;
        $foundMetadataObject = 0;
        $foundMetadataString = 0;
        $pathNotFound = 0;
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

            $metadataType = $metadata->metadata_type;

            $this->info('dataset mongo id: ' . $datasetMongoId . ', new lead time: ' . $leadTime . ', datasetId: ' . $dataset->id);

            if ($metadataType === 'OBJECT') {
                $foundMetadataObject++;
                $data = json_decode($metadata->metadata, true);
                $path = 'metadata.accessibility.access.deliveryLeadTime';
                $update = $this->updateMetadataObject($data, $path, $latestDatasetVersionId, $leadTime);
                if (!$update) {
                    $this->warn('Path not found');
                    $pathNotFound++;
                }
            }

            if ($metadataType === 'STRING') {
                $foundMetadataString++;
                $data = json_decode(json_decode($metadata->metadata), true);
                $path = 'metadata.accessibility.access.deliveryLeadTime';
                $update = $this->updateMetadataString($data, $path, $latestDatasetVersionId, $leadTime);
                if (!$update) {
                    $this->warn('Path not found');
                    $pathNotFound++;
                }
            }
        }

        echo PHP_EOL . 'Total: ' . $total . PHP_EOL;
        echo 'Dataset Found: ' . ($total - $notFound) . PHP_EOL;
        echo 'Metadata Found Object: ' . $foundMetadataObject . PHP_EOL;
        echo 'Metadata Found String: ' . $foundMetadataString . PHP_EOL;
        echo 'Dataset NotFound: ' . $notFound . PHP_EOL;
        echo 'Dataset Path NotFound (no update): ' . $pathNotFound . PHP_EOL;
        echo 'Datasets updated: ' . ($total - $notFound - $pathNotFound) . PHP_EOL;
    }

    private function updateMetadataString(array $data, string $path, int $datasetVersionId, string $leadTime)
    {
        if (Arr::has($data, $path)) {
            Arr::set($data, $path, $leadTime);

            DatasetVersion::where([
                'id' => $datasetVersionId,
            ])->update([
                'metadata' => json_encode(json_encode($data)),
            ]);
            $this->info('metadata was updated');

            return true;
        } else {
            return false;
        }
    }

    private function updateMetadataObject(array $data, string $path, int $datasetVersionId, string $leadTime)
    {
        if (Arr::has($data, $path)) {
            Arr::set($data, $path, $leadTime);

            DatasetVersion::where([
                'id' => $datasetVersionId,
            ])->update([
                'metadata' => json_encode($data),
            ]);
            $this->info('metadata was updated');

            return true;
        } else {
            return false;
        }

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
