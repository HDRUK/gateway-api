<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;

trait RefreshDatabaseLite
{
    protected static bool $migrated = false;
    protected static $databaseConnection;

    public function liteSetUp(): void
    {
        if (!static::$migrated) {
            $template = database_path('testing/template.sqlite');

            if (file_exists($template)) {
                static::$databaseConnection = $this->cloneTemplateIntoMemory($template);
            } else {
                // Fallback for local runs where the template hasn't been built,
                // e.g. `composer run testing:build-template` was never run.
                Artisan::call('migrate');
                Artisan::call('db:seed', ['--class' => 'BaseDatabaseSeeder']);
                static::$databaseConnection = DB::connection()->getPdo();
            }

            static::$migrated = true;
        }

        // Reuse the same connection across tests (fix for SQLite in-memory)
        DB::connection()->setPdo(static::$databaseConnection);

        // Start a manual transaction
        DB::beginTransaction();
    }

    private function cloneTemplateIntoMemory(string $template): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('ATTACH DATABASE '.$pdo->quote($template).' AS tmpl');

        $tables = $pdo->query(
            "SELECT name, sql FROM tmpl.sqlite_master WHERE type = 'table' AND name != 'sqlite_sequence'"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tables as $table) {
            $pdo->exec($table['sql']);
            $pdo->exec("INSERT INTO \"{$table['name']}\" SELECT * FROM tmpl.\"{$table['name']}\"");
        }

        $indexes = $pdo->query(
            "SELECT sql FROM tmpl.sqlite_master WHERE type = 'index' AND sql IS NOT NULL"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($indexes as $sql) {
            $pdo->exec($sql);
        }

        $pdo->exec('DETACH DATABASE tmpl');

        return $pdo;
    }

    public function tearDown(): void
    {
        // Rollback after each test
        DB::rollBack();

        parent::tearDown();
    }
}
