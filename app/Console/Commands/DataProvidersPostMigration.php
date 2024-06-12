<?php

namespace App\Console\Commands;

use Exception;

use App\Models\Team;
use App\Models\Dataset;
use App\Models\DataProvider;
use Illuminate\Console\Command;
use App\Models\DataProviderColl;

use App\Models\DataProviderHasTeam;

use App\Models\DataProviderCollHasTeam;
use MetadataManagementController AS MMC;

class DataProvidersPostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:data-providers-post-migration';

    /**
     * The file of migration mappings translated to CSV array
     * 
     * @var array
     */
    private $csvData = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to populate data providers table from migrated datasets.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->readMigrationFile(storage_path() . '/migration_files/data_providers_mini_seeder.csv');

        // Traverse the CSV data and seed data providers
        foreach ($this->csvData as $csv) {
            try {
                $isDataProvider = $csv['data_provider'] === 'Yes' ? true : false;
                if ($isDataProvider) {
                    // Create a new provider with the team name
                    $newProvider = DataProviderColl::create([
                        'name' => $csv['name'],
                        'img_url' => 'http://placeholder.com',
                        'enabled' => true
                    ]);
                    
                    $team = Team::where('name', $csv['name'])->first();
                    if (!$team) {
                        continue;
                    }

                    DataProviderCollHasTeam::create([
                        'data_provider_coll_id' => $newProvider['id'],
                        'team_id' => $team['id']
                    ]);

                    $this->indexElasticDataProvider($newProvider->id);
                }
            } catch (Exception $e) {
                echo 'unable to process ' . $csv['name'] . ' because ' . $e->getMessage() . "\n";
            }
        }
        echo 'completed seeding of data provider colls';
    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== FALSE) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[$headers[$key]] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);        
    }

    /**
     * Insert data provider document into elastic index
     *
     * @param integer $id
     * @return void
     */
    private function indexElasticDataProvider(int $id): void 
    {
        $provider = DataProviderColl::where('id', $id)->with('teams')->first();

        $datasetTitles = array();
        $locations = array();
        foreach ($provider['teams'] as $team) {
            $datasets = Dataset::where('team_id', $team['id'])->with(['versions', 'spatialCoverage'])->get();
            foreach ($datasets as $dataset) {
                $metadata = $dataset['versions'][0];
                $datasetTitles[] = $metadata['metadata']['metadata']['summary']['shortTitle'];
                foreach ($dataset['spatialCoverage'] as $loc) {
                    if (!in_array($loc['region'], $locations)) {
                        $locations[] = $loc['region'];
                    }
                }
            }
        }
        usort($datasetTitles, 'strcasecmp');

        try {
            $toIndex = [
                'name' => $provider['name'],
                'datasetTitles' => $datasetTitles,
                'geographicLocation' => $locations,
            ];
            $params = [
                'index' => 'dataprovider',
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];

            $client = MMC::getElasticClient();
            $client->index($params);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
