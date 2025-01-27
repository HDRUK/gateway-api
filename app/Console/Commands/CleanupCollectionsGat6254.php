<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use App\Models\CollectionHasDatasetVersion;

class CleanupCollectionsGat6254 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-collections-gat6254 {collId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GAT-6254 :: The command that removes duplicates for the relationship between collections and dataset_versions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collId = $this->argument('collId');
        $collections = null;

        if ($collId) {
            $this->info('collection id: ' . $collId);
            $collections = CollectionHasDatasetVersion::where('collection_id', $collId)->select('collection_id')->get()->toArray();
        } else {
            $this->info('all collections');
            $collections = CollectionHasDatasetVersion::select('collection_id')->get()->toArray();
        }

        if (count($collections) === 0) {
            $this->error('No collection found for ' . $collId . '!');
            return Command::FAILURE;
        }

        $collectionIds = array_unique(convertArrayToArrayWithKeyName($collections, 'collection_id'));

        foreach ($collectionIds as $collectionId) {
            $collectionDatasetVersions = CollectionHasDatasetVersion::where('collection_id', $collectionId)->select('dataset_version_id')->get()->toArray();
            $collectionDatasetVersionIds = array_unique(convertArrayToArrayWithKeyName($collectionDatasetVersions, 'dataset_version_id'));

            foreach ($collectionDatasetVersionIds as $collectionDatasetVersionId) {
                // checking if dataset_versions.id exists in the dataset_versions table
                $datasetVersion = DatasetVersion::where('id', $collectionDatasetVersionId)->select('dataset_id')->first();
                if (is_null($datasetVersion)) {
                    CollectionHasDatasetVersion::where([
                        'collection_id' => $collectionId,
                        'dataset_version_id' => $collectionDatasetVersionId,
                    ])->delete();
                    $this->warn('1. delete collection_id = ' . $collectionId . ' &  dataset_version_id = ' . $collectionDatasetVersionId);
                    continue;
                }

                // checking if datasets.id based on dataset_versions.id exists in the datasets table
                $dataset = Dataset::where('id', $datasetVersion->dataset_id)->select('id')->first();
                if (is_null($dataset)) {
                    CollectionHasDatasetVersion::where([
                        'collection_id' => $collectionId,
                        'dataset_version_id' => $collectionDatasetVersionId,
                    ])->delete();
                    $this->warn('2. delete collection_id = ' . $collectionId . ' &  dataset_version_id = ' . $collectionDatasetVersionId);
                    continue;
                }

                $latestDatasetVersionId = Dataset::where('id', (int) $dataset->id)->select('id')->first()->latestVersion()->id;
                if ((int) $latestDatasetVersionId === (int) $collectionDatasetVersionId) {
                    continue;
                }

                if ((int) $latestDatasetVersionId !== (int) $collectionDatasetVersionId) {
                    $checkCollectionHasDatasetVersion = CollectionHasDatasetVersion::where([
                        'collection_id' => $collectionId,
                        'dataset_version_id' => $latestDatasetVersionId,
                    ])->first();

                    if (!is_null($checkCollectionHasDatasetVersion)) {
                        $this->addRelationWithLatestDatasetVersion($collectionId, $collectionDatasetVersionId, $latestDatasetVersionId);
                        $this->warn('3. delete collection_id = ' . $collectionId . ' &  dataset_version_id = ' . $collectionDatasetVersionId);
                        continue;
                    }

                    if (is_null($checkCollectionHasDatasetVersion)) {
                        $this->addRelationWithLatestDatasetVersion($collectionId, $collectionDatasetVersionId, $latestDatasetVersionId);
                        $this->warn('4. create collection_id = ' . $collectionId . ' &  dataset_version_id = ' . $collectionDatasetVersionId);
                        continue;
                    }
                }
            }
        }

        $this->info('Command completed successfully.');
        return Command::SUCCESS;
    }

    public function addRelationWithLatestDatasetVersion($collectionId, $collectionDatasetVersionId, $latestDatasetVersionId)
    {
        $getCollectionHasDatasetVersion = CollectionHasDatasetVersion::where([
            'collection_id' => $collectionId,
            'dataset_version_id' => $collectionDatasetVersionId,
        ])->first();

        CollectionHasDatasetVersion::create([
            'collection_id' => $collectionId,
            'dataset_version_id' => $latestDatasetVersionId,
            'user_id' => $getCollectionHasDatasetVersion->user_id,
        ]);

        CollectionHasDatasetVersion::create([
            'collection_id' => $collectionId,
            'dataset_version_id' => $collectionDatasetVersionId,
        ])->delete();
    }
}
