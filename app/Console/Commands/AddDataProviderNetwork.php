<?php

namespace App\Console\Commands;

use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Team;
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

        if ($askDataProviderNetwork === 'all') {
            foreach ($csvData as $item) {
                $teamName = strtoupper(trim($item['Data provider/Team']));
                $dataProviderName = strtoupper(trim($item['Data provider network']));

                $return = $this->dataProviderNetworkTeam($dataProviderName, $teamName);
                if (!$return) {
                    continue;
                }
            }
        }

        if ($askDataProviderNetwork !== 'all') {
            $inputDataProviderName = strtoupper(trim($askDataProviderNetwork));

            foreach ($csvData as $item) {
                $teamName = strtoupper(trim($item['Data provider/Team']));
                $dataProviderName = strtoupper(trim($item['Data provider network']));

                if ($inputDataProviderName !== $dataProviderName) {
                    echo 'Found Data Provider Network name ' . $dataProviderName . '. skipping ...' . PHP_EOL;
                    continue;
                }

                $return = $this->dataProviderNetworkTeam($dataProviderName, $teamName);
                if (!$return) {
                    continue;
                }
            }
        }
    }

    private function dataProviderNetworkTeam(string $dataProviderNetworkName, string $teamName): bool
    {
        // check in teams
        $team = Team::where('name', $teamName)->first();
        if (!$team) {
            echo 'Team ' . $teamName . ' not found. skipping ...' . PHP_EOL;
            return false;
        }

        // check if exists DataProviderNetwork and/or create one
        $dataProviderNetwork = DataProviderColl::where('name', $dataProviderNetworkName)->withTrashed()->first();
        if (!$dataProviderNetwork) {
            $dataProviderNetwork = DataProviderColl::create([
                'name' => $dataProviderNetworkName,
                'enabled' => 1,
                'img_url' => null,
            ]);

            echo 'Data Provider network with name ' . $dataProviderNetworkName . ' was created.' . PHP_EOL;
        }

        // check if DataProviderNetwork & Team exists
        $dataProviderNetworkTeam = DataProviderCollHasTeam::where([
            'data_provider_coll_id' => (int) $dataProviderNetwork->id,
            'team_id' => (int) $team->id,
        ])->first();
        if ($dataProviderNetworkTeam) {
            echo 'The team ' . $teamName . ' found in relation with ' .
                $dataProviderNetworkName . '. skipping ...' . PHP_EOL;
            return false;
        }

        // add DataProviderNetwork with Team
        $createDataProviderNetworkTeam = DataProviderCollHasTeam::create([
            'data_provider_coll_id' => (int) $dataProviderNetwork->id,
            'team_id' => (int) $team->id,
        ]);
        echo 'Was created relation between team (' . $teamName . ') and data provider network (' .
            $dataProviderNetworkName . '). ID: ' . $createDataProviderNetworkTeam->id . PHP_EOL;

        return true;
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
