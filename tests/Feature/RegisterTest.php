<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\Traits\MockExternalApis;

class RegisterTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/register';

    protected $user = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

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

        $this->user2 = [
            'name' => Config::get('constants.test.non_admin.name'),
            'firstname' => Config::get('constants.test.non_admin.firstname'),
            'lastname' => Config::get('constants.test.non_admin.lastname'),
            'email' => Config::get('constants.test.non_admin.email'),
            'password' => Config::get('constants.test.non_admin.email'),
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

        $responseFirst = $this->json('POST', self::TEST_URL, $this->user2, ['Accept' => 'application/json']);
        $responseFirst->assertStatus(200);

        $responseSecond = $this->json('POST', self::TEST_URL, $this->user2, ['Accept' => 'application/json']);
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
