<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Trait for seeders that truncate tables with foreign keys on MySQL.
 * Disables FK checks only when not in testing and driver is MySQL.
 */
trait DisablesForeignKeyChecks
{
    protected function disableForeignKeyChecks(): void
    {
        if (!app()->environment('testing') && strtolower(DB::connection()->getDriverName()) === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }
    }

    protected function enableForeignKeyChecks(): void
    {
        if (!app()->environment('testing') && strtolower(DB::connection()->getDriverName()) === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }
}
