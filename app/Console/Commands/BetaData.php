<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BetaData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:beta-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Beta test users and teams';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting migration for demo data");

        $this->info("Running migrate:fresh");
        Artisan::call('migrate:fresh');

        $this->info("Running seed SectorBetaDemo");
        Artisan::call('db:seed', ['--class' => "Database\Beta\SectorBetaDemo"]);

        $this->info("Running seed PermissionBetaDemo");
        Artisan::call('db:seed', ['--class' => "Database\Beta\PermissionBetaDemo"]);

        $this->info("Running seed RoleBetaDemo");
        Artisan::call('db:seed', ['--class' => "Database\Beta\RoleBetaDemo"]);

        $this->info("Running seed TeamBetaDemo");
        Artisan::call('db:seed', ['--class' => "Database\Beta\TeamBetaDemo"]);

        $this->info("Running seed UserBetaDemo");
        Artisan::call('db:seed', ['--class' => "Database\Beta\UserBetaDemo"]);
        
        $this->info("Running seed EmailTemplatesBetaDemo");
        Artisan::call('db:seed', ['--class' => "Database\Beta\EmailTemplatesBetaDemo"]);

        //additional seeders also needed 
        $this->info("Running seed SpatialCoverageSeeder");
        Artisan::call('db:seed', ['--class' => "Database\Seeders\SpatialCoverageSeeder"]);

        $this->info("Running seed KeywordSeeder");
        Artisan::call('db:seed', ['--class' => "Database\Seeders\KeywordSeeder"]);

        $this->info("Completed...");
    }
}
