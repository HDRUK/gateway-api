<?php

namespace Tests\Feature;

use App\Http\Middleware\EncryptCookies;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class OAuth2ControllerTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const AUTHORIZE_URL = '/oauth2/authorize';

    public function setUp(): void
    {
        $this->commonSetUp();
        // EncryptCookies requires APP_KEY; disable it so these tests run
        // in any environment without depending on encryption config.
        $this->withoutMiddleware(EncryptCookies::class);
    }

    /**
     * Missing state param must return 401.
     */
    public function test_authorize_without_state_returns_401(): void
    {
        $response = $this->get(self::AUTHORIZE_URL.'?response_type=code&nonce=abc123');

        $response->assertStatus(401);
    }

    /**
     * A tampered (non-decryptable) state param must return 401, not a 500.
     */
    public function test_authorize_with_tampered_state_returns_401(): void
    {
        $response = $this->get(
            self::AUTHORIZE_URL.'?response_type=code&nonce=abc123&state=this-is-not-a-valid-encrypted-value'
        );

        $response->assertStatus(401);
    }

    /**
     * An empty state param must return 401.
     */
    public function test_authorize_with_empty_state_returns_401(): void
    {
        $response = $this->get(self::AUTHORIZE_URL.'?response_type=code&nonce=abc123&state=');

        $response->assertStatus(401);
    }
}
