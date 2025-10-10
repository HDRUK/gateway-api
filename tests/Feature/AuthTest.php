<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Tests\Traits\Authorization;

class AuthTest extends TestCase
{
    use Authorization;

    public const TEST_URL_AUTH = '/api/v1/auth';

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->user = [
            'name' => Config::get('constants.test.user.name'),
            'email' => Config::get('constants.test.user.email'),
            'password' => Config::get('constants.test.user.password'),
        ];
    }

    /**
     * Test authorization with success
     *
     * @return void
     */
    public function test_authorization_with_success(): void
    {
        $this->authorisationUser();

        $response = $this->json(
            'POST',
            self::TEST_URL_AUTH,
            [
                'email' => $this->user['email'],
                'password' => $this->user['password'],
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(200);
        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('token_type', $response);
    }

    /**
     * Test who generate an exception
     *
     * @return void
     */
    public function test_authorization_generate_exception(): void
    {
        $this->authorisationUser();

        $response = $this->json(
            'POST',
            self::TEST_URL_AUTH,
            [
                'email' => $this->user['email'],
                'password' => 'notfound',
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(500);
    }
}
