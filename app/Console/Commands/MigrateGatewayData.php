<?php

namespace App\Console\Commands;

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

        $srcModel = $this->argument('srcModel');
        $destModel = $this->argument('destModel');

        var_dump($verbose);
        var_dump($dryRun);

        var_dump($srcModel);
        var_dump($destModel);

        $client = new MongoDB\Client(
            Config::get('database.mongodb.dsn')
        );

        var_dump($client);
    }
}
