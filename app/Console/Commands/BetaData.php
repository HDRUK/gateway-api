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
    protected $signature = 'app:beta-data {all?} {user?} {coverage?} {keyword?}';

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
        $all = $this->argument('all');
        $user = $this->argument('user');
        $coverage = $this->argument('coverage');
        $keyword = $this->argument('keyword');

        if ($all) {
            $this->runMigrations($all);
        } elseif ($user) {
            $this->runMigrations($user);
        } elseif ($coverage) {
            $this->runMigrations($coverage);
        }elseif ($keyword) {
            $this->runMigrations($keyword);
        }
    }

    public function runMigrations(string $value)
    {
        $this->info('Starting migration for demo data');

        switch ($value) {
            case 'user': 
                $this->info('Running seed UserBetaDemo');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Beta\UserBetaDemo',
                ]);

                break;

            case 'coverage': 
                $this->info('Running seed SpatialCoverageSeeder');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Seeders\SpatialCoverageSeeder',
                ]);

                break;

            case 'keyword': 
                $this->info('Running seed KeywordSeeder');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Seeders\KeywordSeeder',
                ]);

                break;
            case 'all':
                $this->info('Running migrate:fresh');
                Artisan::call('migrate:fresh');
        
                $this->info('Running seed SectorSeeder');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Seeders\SectorSeeder',
                ]);
        
                $this->info('Running seed PermissionSeeder');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Seeders\PermissionSeeder',
                ]);
        
                $this->info('Running seed RoleSeeder');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Seeders\RoleSeeder',
                ]);
        
                $this->info('Running seed TeamBetaDemo');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Beta\TeamBetaDemo',
                ]);
        
                $this->info('Running seed UserBetaDemo');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Beta\UserBetaDemo',
                ]);
                
                $this->info('Running seed EmailTemplatesSeeder');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Seeders\EmailTemplatesSeeder',
                ]);

                $this->info('Running seed SpatialCoverageSeeder');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Seeders\SpatialCoverageSeeder',
                ]);

                $this->info('Running seed KeywordSeeder');
                Artisan::call('db:seed', [
                    '--class' => 'Database\Seeders\KeywordSeeder',
                ]);

                break;            
        }

        $this->info('Completed...');
    }
}
