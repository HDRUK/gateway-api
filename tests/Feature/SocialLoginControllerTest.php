<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialLoginControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'gateway.gateway_url' => 'https://gateway.test',
            'services.registry.web_url' => 'https://registry.test',
            'services.registry.login_path' => '/en/keycloak',
            'services.registry.api_url' => 'https://api.registry.test/api/v1',
            'services.registry.handoff_secret' => 'test-handoff-secret',
        ]);
    }

    public function test_registry_callback_redirects_to_conflict_page_on_duplicate_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'duplicate@example.com',
            'providerid' => 'existing-sub',
            'provider' => 'registry',
        ]);

        Http::fake([
            'https://api.registry.test/api/v1/auth/gateway_handoff/*/redeem' => Http::response([
                'data' => [
                    'sub' => 'a-brand-new-sub',
                    'given_name' => 'Duplicate',
                    'family_name' => 'User',
                    'email' => $existingUser->email,
                ],
            ], 200),
        ]);

        $response = $this->get('/api/v1/auth/registry/callback?code=some-handoff-code');

        $response->assertRedirect('https://gateway.test/error/409');

        $this->assertSame(
            1,
            User::where('email', 'duplicate@example.com')->count(),
            'a colliding insert should not have created a second row'
        );
    }

    public function test_registry_callback_redirects_to_generic_error_page_when_handoff_redemption_fails(): void
    {
        Http::fake([
            'https://api.registry.test/api/v1/auth/gateway_handoff/*/redeem' => Http::response([], 404),
        ]);

        $response = $this->get('/api/v1/auth/registry/callback?code=some-handoff-code');

        $response->assertRedirect('https://gateway.test/error/500');
    }
}
