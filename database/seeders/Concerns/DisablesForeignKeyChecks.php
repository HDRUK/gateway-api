<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait DisablesForeignKeyChecks
{
    protected function disableForeignKeyChecks(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            return;
        }

        if ($driver === 'pgsql') {
            // Best-effort: disable triggers for the current session.
            DB::statement("SET session_replication_role = 'replica';");
            return;
        }
    }

    protected function enableForeignKeyChecks(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("SET session_replication_role = 'origin';");
            return;
        }
    }
}
