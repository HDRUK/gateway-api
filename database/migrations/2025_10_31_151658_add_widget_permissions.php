<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $permissions = [
            'widgets.create',
            'widgets.read',
            'widgets.update',
            'widgets.delete',
        ];

        $permissionIds = [];
        foreach ($permissions as $name) {
            $permission = DB::table('permissions')
                ->where('name', $name)
                ->where('application', 'gateway')
                ->first();

            if (! $permission) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'application' => 'gateway'
                ]);
            } else {
                $id = $permission->id;
            }

            $permissionIds[] = $id;
        }

        $developerRole = DB::table('roles')->where('name', 'developer')->first();

        if ($developerRole) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $developerRole->id)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $developerRole->id,
                        'permission_id' => $permissionId,
                    ]);
                }
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

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->where('application', 'gateway')
            ->pluck('id')
            ->toArray();

        $developerRole = DB::table('roles')->where('name', 'developer')->first();

        if ($developerRole) {
            DB::table('role_has_permissions')
                ->where('role_id', $developerRole->id)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('name', $permissions)
            ->where('application', 'gateway')
            ->delete();
    }
};
