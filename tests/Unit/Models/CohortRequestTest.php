<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Enums\CohortRequestStatus;
use App\Models\CohortRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

class CohortRequestTest extends TestCase
{
    private function makeCohortRequest(array $attributes = []): CohortRequest
    {
        $user = User::factory()->create();

        return CohortRequest::create(array_merge([
            'user_id' => $user->id,
            'request_status' => CohortRequestStatus::APPROVED,
            'request_expire_at' => null,
        ], $attributes));
    }

    public function test_renewal_eligibility_is_eligible_for_approved(): void
    {
        $cohortRequest = $this->makeCohortRequest([
            'request_status' => CohortRequestStatus::APPROVED,
        ]);

        $this->assertSame(CohortRequest::RENEWAL_ELIGIBLE, $cohortRequest->renewalEligibility());
    }

    public function test_renewal_eligibility_is_already_renewing_when_status_renewing(): void
    {
        $cohortRequest = $this->makeCohortRequest([
            'request_status' => CohortRequestStatus::RENEWING,
        ]);

        $this->assertSame(CohortRequest::RENEWAL_ALREADY_RENEWING, $cohortRequest->renewalEligibility());
    }

    public function test_renewal_eligibility_is_not_applicable_for_other_statuses(): void
    {
        foreach ([
            CohortRequestStatus::PENDING,
            CohortRequestStatus::REJECTED,
            CohortRequestStatus::BANNED,
            CohortRequestStatus::SUSPENDED,
            CohortRequestStatus::EXPIRED,
        ] as $status) {
            $cohortRequest = $this->makeCohortRequest(['request_status' => $status]);

            $this->assertSame(
                CohortRequest::RENEWAL_NOT_APPLICABLE,
                $cohortRequest->renewalEligibility(),
                "Expected NOT_APPLICABLE for status {$status->value}"
            );
        }
    }

    public function test_has_access_is_true_for_approved_within_expiry(): void
    {
        $cohortRequest = $this->makeCohortRequest([
            'request_status' => CohortRequestStatus::APPROVED,
            'request_expire_at' => Carbon::now()->addDays(30),
        ]);

        $this->assertTrue(CohortRequest::hasAccess($cohortRequest));
    }

    public function test_has_access_is_true_for_renewing_within_expiry(): void
    {
        $cohortRequest = $this->makeCohortRequest([
            'request_status' => CohortRequestStatus::RENEWING,
            'request_expire_at' => Carbon::now()->addDays(30),
        ]);

        $this->assertTrue(CohortRequest::hasAccess($cohortRequest));
    }

    public function test_has_access_is_false_for_approved_past_its_true_expiry(): void
    {
        // Simulates the gap before the nightly CohortUserExpiry job has run:
        // request_status still says APPROVED, but the true expiry has passed.
        $cohortRequest = $this->makeCohortRequest([
            'request_status' => CohortRequestStatus::APPROVED,
            'request_expire_at' => Carbon::now()->subDay(),
        ]);

        $this->assertFalse(CohortRequest::hasAccess($cohortRequest));
    }

    public function test_has_access_is_false_for_renewing_past_its_true_expiry(): void
    {
        $cohortRequest = $this->makeCohortRequest([
            'request_status' => CohortRequestStatus::RENEWING,
            'request_expire_at' => Carbon::now()->subDay(),
        ]);

        $this->assertFalse(CohortRequest::hasAccess($cohortRequest));
    }

    public function test_has_access_is_false_for_other_statuses(): void
    {
        foreach ([
            CohortRequestStatus::PENDING,
            CohortRequestStatus::REJECTED,
            CohortRequestStatus::BANNED,
            CohortRequestStatus::SUSPENDED,
            CohortRequestStatus::EXPIRED,
        ] as $status) {
            $cohortRequest = $this->makeCohortRequest(['request_status' => $status]);

            $this->assertFalse(
                CohortRequest::hasAccess($cohortRequest),
                "Expected hasAccess to be false for status {$status->value}"
            );
        }
    }
}
