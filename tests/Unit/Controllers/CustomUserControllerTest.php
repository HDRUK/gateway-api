<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Enums\CohortRequestStatus;
use App\Http\Controllers\SSO\CustomUserController;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasPermission;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomUserControllerTest extends TestCase
{
    public function test_user_info_includes_roles_for_renewing_status(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

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

        $response = (new CustomUserController())->userInfo(new Request());
        $content = $response->getData(true);

        $this->assertSame(['GENERAL_ACCESS'], $content['cohort_discovery_roles']);
        $this->assertSame(['GENERAL_ACCESS'], $content['rquestroles']);
    }

    public function test_user_info_has_empty_roles_for_pending_status(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        CohortRequest::create([
            'user_id' => $user->id,
            'request_status' => CohortRequestStatus::PENDING,
        ]);

        $response = (new CustomUserController())->userInfo(new Request());
        $content = $response->getData(true);

        $this->assertSame([], $content['cohort_discovery_roles']);
    }

    public function test_user_info_returns_full_profile_when_no_cohort_request_exists(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $response = (new CustomUserController())->userInfo(new Request());
        $content = $response->getData(true);

        $this->assertSame($user->id, $content['id']);
        $this->assertSame([], $content['cohort_discovery_roles']);
    }
}
