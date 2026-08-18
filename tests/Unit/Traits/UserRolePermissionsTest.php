<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Enums\CohortRequestStatus;
use App\Http\Traits\UserRolePermissions;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasPermission;
use App\Models\Permission;
use App\Models\User;

class UserRolePermissionsTest extends TestCase
{
    private function callGetCohortUserRoles(int $userId): array
    {
        $host = new class () {
            use UserRolePermissions;
        };

        $method = new \ReflectionMethod($host, 'getCohortUserRoles');
        $method->setAccessible(true);

        return $method->invoke($host, $userId);
    }

    public function test_get_cohort_user_roles_includes_renewing_status(): void
    {
        $user = User::factory()->create();

        $cohortRequest = CohortRequest::create([
            'user_id' => $user->id,
            'request_status' => CohortRequestStatus::RENEWING,
            'request_expire_at' => now()->addDays(30),
        ]);

        $permission = Permission::where([
            'application' => 'cohort',
            'name' => 'GENERAL_ACCESS',
        ])->first();

        CohortRequestHasPermission::create([
            'cohort_request_id' => $cohortRequest->id,
            'permission_id' => $permission->id,
        ]);

        $this->assertSame(['GENERAL_ACCESS'], $this->callGetCohortUserRoles($user->id));
    }

    public function test_get_cohort_user_roles_empty_for_pending_status(): void
    {
        $user = User::factory()->create();

        CohortRequest::create([
            'user_id' => $user->id,
            'request_status' => CohortRequestStatus::PENDING,
        ]);

        $this->assertSame([], $this->callGetCohortUserRoles($user->id));
    }
}
