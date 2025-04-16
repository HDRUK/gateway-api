<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

trait RunMigrationOnce
{
    protected static bool $migrated = false;
    // protected static $databaseConnection;

    public function runMigrationsOnce(): void
    {
        if (!static::$migrated) {
            Artisan::call('migrate:fresh');
            $this->disableObservers();
            $this->dbSeeder();
            static::$migrated = true;
        }
    }

    protected function disableObservers()
    {
        Model::unsetEventDispatcher();
    }

    protected function dbSeeder()
    {
        $seeders = [
            'Database\\Seeders\\MinimalUserSeeder',
            'Database\\Seeders\\ActivityLogTypeSeeder',
            'Database\\Seeders\\ActivityLogUserTypeSeeder',
            'Database\\Seeders\\ActivityLogSeeder',
            'Database\\Seeders\\AuditLogSeeder',
            'Database\\Seeders\\SpatialCoverageSeeder',
            'Database\\Seeders\\SectorSeeder',
            'Database\\Seeders\\CategorySeeder',
            'Database\\Seeders\\TypeCategorySeeder',
            'Database\\Seeders\\ProgrammingLanguageSeeder',
            'Database\\Seeders\\ProgrammingPackageSeeder',
            'Database\\Seeders\\LicenseSeeder',
            'Database\\Seeders\\TagSeeder',
            'Database\\Seeders\\KeywordSeeder',
            'Database\\Seeders\\ApplicationSeeder',
            'Database\\Seeders\\CollectionSeeder',
            'Database\\Seeders\\DatasetSeeder',
            'Database\\Seeders\\DatasetVersionSeeder',
            'Database\\Seeders\\ToolSeeder',
            'Database\\Seeders\\DurSeeder',
            'Database\\Seeders\\CollectionHasKeywordSeeder',
            'Database\\Seeders\\CollectionHasDatasetVersionSeeder',
            'Database\\Seeders\\CollectionHasToolSeeder',
            'Database\\Seeders\\CollectionHasDurSeeder',
            'Database\\Seeders\\PublicationSeeder',
            'Database\\Seeders\\PublicationHasDatasetVersionSeeder',
            'Database\\Seeders\\CollectionHasPublicationSeeder',
            'Database\\Seeders\\CollectionHasUserSeeder',
            'Database\\Seeders\\EmailTemplateSeeder',
        ];

        foreach ($seeders as $seederClass) {
            // var_dump($seeders);
            Artisan::call('db:seed', [
                '--class' => $seederClass,
            ]);
        }
    }

    public function tearDown(): void
    {
        // Rollback after each test
        DB::rollBack();

        parent::tearDown();
    }
}
