<?php

namespace Tests\Feature;

use Hash;
use Config;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    const TEST_URL_REGISTER = '/api/v1/register';

    protected $user = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->user = [
            'name' => Config::get('constants.test.user.name'),
            'email' => Config::get('constants.test.user.email'),
            'password' => Config::get('constants.test.user.email'),
        ];
    }

    /**
     * Test register user with success
     * 
     * @return void
     */
    public function test_register_user_with_success_in_database(): void
    {
        $response = $this->json('POST', self::TEST_URL_REGISTER, $this->user, ['Accept' => 'application/json']);

        $this->assertIsObject(
            $response,
            "actual content is object"
        );
        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('token_type', $response);
    }

    /**
     * Test who generate an exception
     *
     * @return void
     */
    public function test_register_user_and_generate_exception(): void
    {
        $this->withExceptionHandling();
        $this->createNewEntryInUserTable();
        $response = $this->json('POST', self::TEST_URL_REGISTER, $this->user, ['Accept' => 'application/json']);
        $response->assertStatus(500);
    }

    private function createNewEntryInUserTable()
    {
        $user = [
            'name' => $this->user['name'],
            'firstname' => null,
            'lastname' => null,
            'email' => $this->user['email'],
            'provider' => Config:: get('constants.provider.service'),
            'password' => Hash::make($this->user['password']),
        ];
        return User::create($user);
    }
}
