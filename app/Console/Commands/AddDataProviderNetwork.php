<?php

namespace App\Console\Commands;

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

    private $csvDataFromFile = [];

    public function __construct()
    {
        parent::__construct();
        $this->csvDataFromFile = $this->readMigrationFile(storage_path() . '/migration_files/data_provider_networkv3.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $askDataProviderNetwork = $this->ask('Data Provider Network for import, based on file "../storage/migration_files/data_provider_networkv3.csv"?', 'all');

        $csv = $this->csvData($this->csvDataFromFile);

        if ($csv === 'all') {

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
                $item[$headers[$key]] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);        
    }
}
