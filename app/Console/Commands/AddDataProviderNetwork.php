<?php

namespace App\Console\Commands;

use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use Illuminate\Console\Command;

class AddDataProviderNetwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-data-provider-network';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Data Provider Network for import, based on file "../storage/migration_files/data_provider_networkv3.csv"';

    private $csvData = [];

    public function __construct()
    {
        parent::__construct();
        $this->readMigrationFile(storage_path() . '/migration_files/data_provider_networkv3.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $askDataProviderNetwork = $this->ask('Data Provider Network for import, based on file "../storage/migration_files/data_provider_networkv3.csv"? [default value all]', 'all');
        $askInitDataProviderNetwork = $this->ask('Do you want to initialize the database for "Data Provider Network"? yes/no [default value no]', 'no');

        if ($askInitDataProviderNetwork === 'yes') {
            DataProviderCollHasTeam::truncate();
            DataProviderColl::truncate();
        }

        $csvData = $this->csvData;

        dd($csvData);

        if ($csv === 'all') {
            foreach ($csvData as $key => $value) {
                // check in teams

                // check if exists DataProviderNetwork

                // check if DataProviderNetwork & Team exists

                // create new DataProviderNetwork

                // add DataProviderNetwork with Team
            }
        }

        if ($csv !== 'all') {
            // check in teams

            // check if exists DataProviderNetwork

            // check if DataProviderNetwork & Team exists

            // create new DataProviderNetwork

            // add DataProviderNetwork with Team
        }


        dd($askDataProviderNetwork);
    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== FALSE) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[trim($headers[$key], "\xEF\xBB\xBF")] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);        
    }
}
