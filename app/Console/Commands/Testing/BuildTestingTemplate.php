<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BuildTestingTemplate extends Command
{
    protected $signature = 'testing:build-template {--force : Rebuild even if the template is already up to date}';

    protected $description = 'Build a migrated + seeded SQLite template database that test workers can clone instead of migrating/seeding from scratch';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('testing:build-template must run with the sqlite connection as default (set DB_CONNECTION=sqlite).');

            return self::FAILURE;
        }

        $templatePath = database_path('testing/template.sqlite');
        $hashPath = database_path('testing/template.hash');
        $hash = $this->currentMigrationsHash();

        if (! $this->option('force') && File::exists($templatePath) && File::exists($hashPath) && File::get($hashPath) === $hash) {
            $this->info('Testing template is already up to date, skipping rebuild.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($templatePath));
        File::delete($templatePath);
        touch($templatePath);

        // Point the default 'sqlite' connection at the template file for this
        // process only, so every package resolving the 'sqlite' connection
        // (e.g. Pennant's database store) writes to the same place.
        config(['database.connections.sqlite.database' => $templatePath]);
        DB::purge('sqlite');

        // Mirror Tests\TestCase, which seeds with model events disabled so
        // seeding doesn't trigger observers that hit real external services
        // (e.g. Elasticsearch indexing) via model creation events.
        Model::unsetEventDispatcher();

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => 'BaseDatabaseSeeder', '--force' => true]);

        File::put($hashPath, $hash);

        $this->info("Testing template built at {$templatePath}.");

        return self::SUCCESS;
    }

    private function currentMigrationsHash(): string
    {
        $files = collect(File::allFiles(database_path('migrations')))
            ->map(fn ($file) => $file->getFilename().':'.$file->getMTime())
            ->sort()
            ->implode('|');

        return md5($files);
    }
}
