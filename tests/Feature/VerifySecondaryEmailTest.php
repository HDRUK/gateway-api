<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Tests\TestCase;

class VerifySecondaryEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_secondary_email_verification()
    {
        $user = User::factory()->create();
        $token = EmailVerification::create([
            'uid' => $uuid = Str::uuid(),
            'user_id' => $user->id,
            'is_secondary' => true,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $response = $this->getJson("/api/v1/users/verify-secondary-email/{$uuid}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Secondary email verified successfully.',
            ]);

        $this->assertNotNull($user->fresh()->secondary_email_verified_at);
        $this->assertDatabaseMissing('email_verifications', ['uid' => $uuid]);
    }

    public function test_expired_token()
    {
        $user = User::factory()->create();
        $token = EmailVerification::create([
            'uid' => $uuid = Str::uuid(),
            'user_id' => $user->id,
            'is_secondary' => true,
            'expires_at' => Carbon::now()->subHour(),
        ]);

        $response = $this->getJson("/api/v1/users/verify-secondary-email/{$uuid}");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Verification link has expired.',
            ]);
    }
}
