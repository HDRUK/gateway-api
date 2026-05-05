<?php

namespace Tests\Feature;

use Config;
use Hash;
use Tests\TestCase;
use App\Models\User;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class AuthTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_AUTH = '/api/v1/auth';
    public const TEST_URL_REGISTER = '/api/v1/auth/register';
    public const TEST_URL_LOGIN = '/api/v1/auth/login';

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

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

    public function test_authorization_with_cruk_provider_success(): void
    {
        $email = 'cruk.user@example.com';
        $password = 'SecurePassword123!';

        User::create([
            'name' => 'CRUK User',
            'firstname' => 'CRUK',
            'lastname' => 'User',
            'email' => $email,
            'provider' => Config::get('constants.provider.cruk'),
            'password' => Hash::make($password),
            'is_admin' => 0,
        ]);

        $response = $this->json(
            'POST',
            self::TEST_URL_AUTH,
            [
                'email' => $email,
                'password' => $password,
                'provider' => Config::get('constants.provider.cruk'),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(200);
        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('token_type', $response);
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $email = 'register.user@example.com';
        $password = 'SecurePassword123!';

        $response = $this->json(
            'POST',
            self::TEST_URL_REGISTER,
            [
                'email' => $email,
                'password' => $password,
                'firstname' => 'Reg',
                'lastname' => 'User',
                'provider' => Config::get('constants.provider.cruk'),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'email',
                    'name',
                ],
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'provider' => Config::get('constants.provider.cruk'),
        ]);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $email = 'login.user@example.com';
        $password = 'SecurePassword123!';

        User::create([
            'name' => 'Login User',
            'firstname' => 'Login',
            'lastname' => 'User',
            'email' => $email,
            'provider' => Config::get('constants.provider.cruk'),
            'password' => Hash::make($password),
            'is_admin' => 0,
        ]);

        $response = $this->json(
            'POST',
            self::TEST_URL_LOGIN,
            [
                'email' => $email,
                'password' => $password,
                'provider' => Config::get('constants.provider.cruk'),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'email',
                    'name',
                ],
            ],
        ]);
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
