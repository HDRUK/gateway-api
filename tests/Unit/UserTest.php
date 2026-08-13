<?php

namespace Tests\Unit;

use App\Enums\CohortRequestStatus;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasPermission;
use App\Models\Permission;
use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private function makeCohortRequestWithAccess(User $user, string $status): CohortRequest
    {
        $cohortRequest = CohortRequest::create([
            'user_id' => $user->id,
            'request_status' => $status,
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

        return $cohortRequest;
    }

    public function test_cohort_discovery_roles_includes_renewing_status(): void
    {
        $user = User::factory()->create();
        $this->makeCohortRequestWithAccess($user, CohortRequestStatus::RENEWING->value);

        $this->assertSame(['GENERAL_ACCESS'], $user->cohort_discovery_roles);
    }

    public function test_cohort_discovery_roles_empty_for_pending_status(): void
    {
        $user = User::factory()->create();
        $this->makeCohortRequestWithAccess($user, CohortRequestStatus::PENDING->value);

        $this->assertSame([], $user->cohort_discovery_roles);
    }

    public function test_rquest_roles_includes_renewing_status(): void
    {
        $user = User::factory()->create();
        $this->makeCohortRequestWithAccess($user, CohortRequestStatus::RENEWING->value);

        $this->assertSame(['GENERAL_ACCESS'], $user->rquestroles);
    }

    public function test_preload_cohort_data_for_users_includes_renewing_status(): void
    {
        $approvedUser = User::factory()->create();
        $this->makeCohortRequestWithAccess($approvedUser, CohortRequestStatus::APPROVED->value);

        $renewingUser = User::factory()->create();
        $this->makeCohortRequestWithAccess($renewingUser, CohortRequestStatus::RENEWING->value);

        $pendingUser = User::factory()->create();
        $this->makeCohortRequestWithAccess($pendingUser, CohortRequestStatus::PENDING->value);

        User::preloadCohortDataForUsers(collect([$approvedUser, $renewingUser, $pendingUser]));

        $this->assertSame(['GENERAL_ACCESS'], $approvedUser->cohort_discovery_roles);
        $this->assertSame(['GENERAL_ACCESS'], $renewingUser->cohort_discovery_roles);
        $this->assertSame([], $pendingUser->cohort_discovery_roles);
    }

    public function test_that_user_emails_are_unique()
    {
        $user = User::create([
            'firstname' => 'Test',
            'lastname' => 'User I',
            'name' => 'Test User I',
            'email' => 'test_user_i@doesntexist.com',
        ]);

        if ($user) {
            try {
                $otherUser = User::create([
                    'firstname' => 'Test',
                    'lastname' => 'User II',
                    'name' => 'Test User II',
                    'email' => 'test_user_i@doesntexist.com',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $this->assertEquals($e->errorInfo[0], '23000'); // code
                $this->assertEquals($e->errorInfo[2], 'UNIQUE constraint failed: users.email'); // message
            }
        }
    }
}
