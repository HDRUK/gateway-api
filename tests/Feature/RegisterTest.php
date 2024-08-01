<?php

namespace Tests\Feature;

use Hash;
use Config;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\SectorSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    const TEST_URL = '/api/v1/register';

    protected $user = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SectorSeeder::class,
        ]);
      
        $this->runMockHubspot();

        $this->user = [
            'name' => Config::get('constants.test.user.name'),
            'firstname' => Config::get('constants.test.user.firstname'),
            'lastname' => Config::get('constants.test.user.lastname'),
            'email' => Config::get('constants.test.user.email'),
            'password' => Config::get('constants.test.user.email'),
            'sector_id' => 1,
            'contact_feedback' => 1,
            'contact_news' => 1,
        ];
    }

    /**
     * Test register user with success
     * 
     * @return void
     */
    public function test_register_user_with_success_in_database(): void
    {
        $response = $this->json('POST', self::TEST_URL, $this->user, ['Accept' => 'application/json']);
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

        $responseFirst = $this->json('POST', self::TEST_URL, $this->user, ['Accept' => 'application/json']);
        $responseFirst->assertStatus(200);

        $responseSecond = $this->json('POST', self::TEST_URL, $this->user, ['Accept' => 'application/json']);
        $responseSecond->assertStatus(400);
    }

    public function runMockHubspot()
    {
        Http::fake([
            // DELETE
            "http://hub.local/contacts/v1/contact/vid/*" => function ($request) {
                if ($request->method() === 'DELETE') {
                    return Http::response([], 200);
                }
            },
        
            // GET (by vid)
            "http://hub.local/contacts/v1/contact/vid/*/profile" => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response(['vid' => 12345, 'properties' => []], 200);
                } elseif ($request->method() === 'POST') {
                    return Http::response([], 204);
                }
            },
        
            // GET (by email)
            "http://hub.local/contacts/v1/contact/email/*/profile" => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response(['vid' => 12345], 200);
                }
            },
        
            // POST (create contact)
            'http://hub.local/contacts/v1/contact' => function ($request) {
                if ($request->method() === 'POST') {
                    return Http::response(['vid' => 12345], 200);
                }
            },
        ]);
    }
}
