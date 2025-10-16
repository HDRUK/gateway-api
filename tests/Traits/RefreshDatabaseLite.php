<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

trait RefreshDatabaseLite
{
    protected static bool $migrated = false;
    protected static $databaseConnection;

    public function liteSetUp(): void
    {
        if (!static::$migrated) {
            Artisan::call('migrate');
            Artisan::call('db:seed', ['--class' => 'BaseDatabaseSeeder']);
            static::$migrated = true;

            // Store the connection (for SQLite in-memory)
            static::$databaseConnection = DB::connection()->getPdo();
        }

        // Reuse the same connection across tests (fix for SQLite in-memory)
        DB::connection()->setPdo(static::$databaseConnection);

        // Start a manual transaction
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        // Rollback after each test
        DB::rollBack();

        parent::tearDown();
    }
}
