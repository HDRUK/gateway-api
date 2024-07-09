<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V1\PublicationController;
use App\Models\Publication;
use Illuminate\Console\Command;
use MetadataManagementController AS MMC;

class PublicationTypePostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '`app:publication-type-post-migration `{reindex?}';

    private $csvData = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to post-process migrated publications from mk1 mongo db. Updates Publication::publication_type with values from file by matching publication titles';

    public function __construct()
    {
        parent::__construct();
        $this->csvData = $this->readMigrationFile(storage_path() . '/migration_files/mapped_publications_types.csv');
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
            $paperDOI = $csv['paper_doi'];
            $paperName = $csv['paper_title'];
            $publicationType = $csv['paper_type_GW2'];

            // Find Publication associated to this row
            $publications = Publication::where('paper_doi', $paperDOI)->get();
            if (!$publications) {
                echo 'Failed to find paper with doi ' . $paperDOI . "\n";
                $progressbar->advance();
                sleep(0.1);
                continue;
            }
            
            foreach ($publications as $publication) {
                if ($publication->paper_title != $paperName) {
                    $progressbar->advance();
                    sleep(0.1);
                    echo 'WARNING! Found paper by DOI but titles do not match. Will not create or update this record.' . "\n";
                    echo $publication->paper_title . ' vs ' . $paperName . "\n";
                    continue;
                }

                $publication->publication_type = $publicationType;
            
                $publication->save();
            
                echo 'Updated or created record with id ' . $publication->id . ', doi ' . $paperDOI . ', with publication type ' . $publicationType . "\n";
            
                if ($reindexEnabled) {
                    $publicationController->indexElasticPublication($publication->id);
                }
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
