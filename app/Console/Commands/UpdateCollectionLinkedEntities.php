<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Collection;
use App\Models\Tool;
use App\Models\Publication;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\CollectionHasDatasetVersion;
use App\Models\CollectionHasTool;
use App\Models\CollectionHasDur;
use App\Models\CollectionHasPublication;

class UpdateCollectionLinkedEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-collection-linked-entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update linked entities for mk1 collections';

    /**
     * The file of migration mappings translated to CSV array
     *
     * @var array
     */
    private $csvData = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->readMigrationFile(storage_path() . '/migration_files/missing_collection_linkages.csv');

        $relatedObjectMap = [
            'dataset'        => [Dataset::class, CollectionHasDatasetVersion::class],
            'tool'           => [Tool::class, CollectionHasTool::class],
            'dur'            => [Dur::class, CollectionHasDur::class],
            'publication'    => [Publication::class, CollectionHasPublication::class],
        ];

        $progressbar = $this->output->createProgressBar(count($this->csvData));
        $progressbar->start();


        foreach ($this->csvData as $csv) {
            $collection = Collection::where('mongo_object_id', $csv['collection_mongo_object_id'])->select('id')->firstOrFail();
            if (is_null($collection)) {
                //for dev and preprod
                //this csv was made from prod snapshot
                $progressbar->advance();
                continue;
            }

            list($modelClass, $modelHasClass) = $relatedObjectMap[$csv['related_entity']];
            $relatedModel = null;

            if ($csv['related_entity'] !== 'publication') {
                $relatedModel = $modelClass::where('mongo_object_id', $csv['related_mongo_object_id'])->first();

            }
            if (is_null($relatedModel)) {
                $relatedModel = $modelClass::where('mongo_id', $csv['related_mongo_id'])->first();
            }

            if (is_null($relatedModel)) {
                //cannot find relationship
                $progressbar->advance();
                continue;
            }

            if ($csv['related_entity'] === 'dataset') {
                $relatedModel = $relatedModel->latestVersion(['id']);
            }

            $modelHasClass::updateOrCreate([
                'collection_id'  => $collection->id,
                $csv['related_entity'] . '_id' =>  $relatedModel->id,
            ]);

            $progressbar->advance();

        }


    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[$headers[$key]] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);
    }
}
