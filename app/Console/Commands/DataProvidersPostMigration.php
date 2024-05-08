<?php

namespace App\Console\Commands;

use Exception;

use App\Models\DataProvider;
use App\Models\DataProviderHasTeam;
use App\Models\Dataset;
use App\Models\Team;

use Illuminate\Console\Command;

class DataProvidersPostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:data-providers-post-migration';

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
                    $newProvider = DataProvider::create([
                        'name' => $csv['name'],
                        'img_url' => 'http://placeholder.com',
                        'enabled' => true
                    ]);
                    $team = Team::where('name', $csv['name'])->first();
                    DataProviderHasTeam::create([
                        'data_provider_id' => $newProvider['id'],
                        'team_id' => $team['id']
                    ]);
                }
            } catch (Exception $e) {
                echo 'unable to process ' . $csv['name'] . ' because ' . $e->getMessage() . "\n";
            }
        }
        echo 'completed seeding of data providers';
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
}
