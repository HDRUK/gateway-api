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

            $permissionIds[] = $permission->id;
        }

        $roles = DB::table('roles')
            ->whereIn('name', ['hdruk.superadmin', 'custodian.team.admin'])
            ->get();

        foreach ($roles as $role) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $role->id,
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

        $roles = DB::table('roles')
            ->whereIn('name', ['hdruk.superadmin', 'custodian.team.admin'])
            ->get();

        foreach ($roles as $role) {
            DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
