<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'widgets.create',
            'widgets.read',
            'widgets.update',
            'widgets.delete',
        ];

        foreach ($permissions as $name) {
            $exists = DB::table('permissions')
                ->where('name', $name)
                ->where('application', 'gateway')
                ->exists();

            if (! $exists) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'application' => 'gateway'
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissions = [
            'widgets.create',
            'widgets.read',
            'widgets.update',
            'widgets.delete',
        ];

        DB::table('permissions')
            ->whereIn('name', $permissions)
            ->where('application', 'gateway')
            ->delete();
    }
};
