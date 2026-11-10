<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AddDashboardReadPermissionMigrationTest extends TestCase
{
    private const EXPECTED_ROLES = [
        'custodian.dar.manager',
        'custodian.metadata.manager',
        'custodian.team.admin',
        'custodian.team.cohortAdmin',
        'hdruk.admin',
        'hdruk.superadmin',
    ];

    public function test_grants_dashboard_read_to_exactly_the_dashboard_roles_when_roles_already_exist(): void
    {
        $migration = $this->migration();
        $migration->down();

        $migration->up();

        $this->assertSame(self::EXPECTED_ROLES, $this->rolesWithDashboardRead());
    }

    public function test_running_up_twice_leaves_one_permission_and_one_grant_per_role(): void
    {
        $migration = $this->migration();
        $migration->down();

        $migration->up();
        $migration->up();

        $this->assertSame(1, DB::table('permissions')->where('name', 'dashboard.read')->count());
        $this->assertSame(self::EXPECTED_ROLES, $this->rolesWithDashboardRead());
        $this->assertSame(count(self::EXPECTED_ROLES), $this->dashboardReadGrantCount());
    }

    public function test_down_removes_the_permission_and_its_grants(): void
    {
        $this->migration()->down();

        $this->assertFalse(DB::table('permissions')->where('name', 'dashboard.read')->exists());
        $this->assertSame([], $this->rolesWithDashboardRead());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_10_120000_add_dashboard_read_permission.php');
    }

    /**
     * @return list<string>
     */
    private function rolesWithDashboardRead(): array
    {
        return DB::table('roles')
            ->join('role_has_permissions', 'roles.id', '=', 'role_has_permissions.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', 'dashboard.read')
            ->orderBy('roles.name')
            ->pluck('roles.name')
            ->all();
    }

    private function dashboardReadGrantCount(): int
    {
        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', 'dashboard.read')
            ->count();
    }
}
