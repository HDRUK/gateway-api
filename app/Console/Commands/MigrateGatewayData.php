<?php

namespace App\Console\Commands;

use Config;
use Exception;
use MongoDB\Client;
use Illuminate\Console\Command;

class MigrateGatewayData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-gateway-data
        {srcModel : The MongoDB source model to migrate from}
        {destModel : The local destination model to migrate to}
        {--L|logs : Whether or not to output logs during migration}
        {--D|dryRun : Whether the migration is written to the local database or simulated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates gateway data from MongoDB to MySQL';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $verbose = $this->option('logs');
        $dryRun = $this->option('dryRun');

        $srcModel = strtolower($this->argument('srcModel'));
        $destModel = $this->argument('destModel');

        try {
            $client = new Client(
                Config::get('database.connections.mongodb.dsn'),
                [
                    'serverSelectionTryOnce' => true,
                    'ssl' => false,
                    // 'replicaSet' => 'DevCluster-shard-0',
                    'authSource' => 'admin',
                    // 'readPreference' => 'secondaryPreferred',
                ]
            );

            $collection = ($client)
                ->{Config::get('database.connections.mongodb.database')}
                ->{$srcModel};
            $document = $collection->findOne(['_id' => '5e544facbd427b6e9cd9059c']);

            var_dump($document);
        } catch (Exception $e) {
            printf($e->getMessage());
        }
    }
}
