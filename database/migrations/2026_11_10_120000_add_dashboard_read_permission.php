<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    private const PERMISSION = 'dashboard.read';

    private const ROLES = [
        'hdruk.superadmin',
        'hdruk.admin',
        'custodian.team.admin',
        'custodian.team.cohortAdmin',
        'custodian.metadata.manager',
        'custodian.dar.manager',
    ];

    public function up(): void
    {
        $permission = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('application', 'gateway')
            ->first();

        $permissionId = $permission
            ? $permission->id
            : DB::table('permissions')->insertGetId([
                'name' => self::PERMISSION,
                'application' => 'gateway',
            ]);

        $roleIds = DB::table('roles')->whereIn('name', self::ROLES)->pluck('id');

        foreach ($roleIds as $roleId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('application', 'gateway')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
