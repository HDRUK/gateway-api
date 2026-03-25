<?php

namespace Tests\Feature;

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
