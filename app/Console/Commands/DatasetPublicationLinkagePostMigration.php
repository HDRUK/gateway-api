<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V1\PublicationController;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDataset;
use Illuminate\Console\Command;
use MetadataManagementController AS MMC;

class DatasetPublicationLinkagePostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dataset-publication-linkage-post-migration {reindex?}';

    private $csvData = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to post-process migrated datasets and publications from mk1 mongo db. Updates PublicationHasDataset::link_type with values from file by matching titles';

    public function __construct()
    {
        parent::__construct();
        $this->csvData = $this->readMigrationFile(storage_path() . '/migration_files/datasets_publications_linkages.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reindex = $this->argument('reindex');
        $reindexEnabled = $reindex !== null;

        $publicationController = new PublicationController();
        $progressbar = $this->output->createProgressBar(count($this->csvData));
        $progressbar->start();

        foreach ($this->csvData as $csv) {
            $datasetTitle = $csv['dataset_title'];
            $paperDOI = $csv['paper_doi'];
            $paperName = $csv['paper_name'];
            $linkType = $csv['Relationship'];

            // Find DatasetVersion associated to this row
            $datasetVersion = DatasetVersion::whereRaw(
                "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.title')) LIKE LOWER(?)",
                ["%$datasetTitle%"]
            )->latest('version')->first();
            if (!$datasetVersion) {
                echo 'Failed to find dataset with title ' . $datasetTitle . "\n";
                $progressbar->advance();
                sleep(0.1);
                continue;
            }

            // Find Dataset ID associated to this row
            $datasetId = $datasetVersion->dataset_id;

            // Find Publication associated to this row
            $publication = Publication::where('paper_doi', $paperDOI)->first();
            if (!$publication) {
                echo 'Failed to find paper with doi ' . $paperDOI . "\n";
                $progressbar->advance();
                sleep(0.1);
                continue;
            }

            $publicationId = $publication->id;

            // Check that the supplied Publication name matches the one found
            if ($publication->paper_title != $paperName) {
                $progressbar->advance();
                sleep(0.1);
                echo 'WARNING! Found paper by DOI but titles do not match. Will not create or update this record.' . "\n";
                echo $publication->paper_title . ' vs ' . $paperName . "\n";
                continue;
            }

            // Since we have both records, create or update a new record in PublicationHasDataset with the supplied link type.
            $publication_has_dataset = PublicationHasDataset::where([['publication_id', '=', (int) $publication->id],
                        ['dataset_id', '=', (int) $datasetId]])->first();
            
            PublicationHasDataset::updateOrCreate(
                [
                    'publication_id' => (int) $publicationId,
                    'dataset_id' => (int) $datasetId
                ],
                ['link_type' => $linkType]
            );

            echo 'Updated or created record with publication_id ' . $publicationId . ', dataset_id ' . $datasetId . ', and link type ' . $linkType . "\n";

            if ($reindexEnabled) {
                $publicationController->indexElasticPublication($publicationId);
            }

            $progressbar->advance();
            sleep(0.1);
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
