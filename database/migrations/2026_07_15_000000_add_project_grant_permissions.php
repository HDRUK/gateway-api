<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    private const PERMISSIONS = [
        'project_grants.read',
        'project_grants.create',
        'project_grants.update',
        'project_grants.delete',
    ];

    // Mirrors the roles that hold papers.* permissions in RoleSeeder.
    private const ROLES = [
        'custodian.team.admin',
        'custodian.metadata.manager',
        'custodian.dar.manager',
        'hdruk.superadmin',
    ];

    public function up(): void
    {
        $permissionIds = [];
        foreach (self::PERMISSIONS as $name) {
            $permission = DB::table('permissions')
                ->where('name', $name)
                ->where('application', 'gateway')
                ->first();

            if (!$permission) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'application' => 'gateway',
                ]);
            } else {
                $id = $permission->id;
            }

            $permissionIds[] = $id;
        }

        foreach (self::ROLES as $roleName) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            if (!$role) {
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
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
        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::PERMISSIONS)
            ->where('application', 'gateway')
            ->pluck('id')
            ->toArray();

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('name', self::PERMISSIONS)
            ->where('application', 'gateway')
            ->delete();
    }
};
