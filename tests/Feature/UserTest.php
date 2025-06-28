<?php

namespace Tests\Feature;

use Hash;
use Tests\TestCase;
use App\Models\User;
use Tests\Traits\Authorization;
use Database\Seeders\SectorSeeder;
use Illuminate\Support\Facades\Http;
// use Illuminate\Foundation\Testing\WithFaker;
use Database\Seeders\MinimalUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    public const TEST_URL = '/api/v1/users';

    protected $header = [];
    protected $adminJwt = '';
    protected $nonAdminJwt = '';

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->runMockHubspot();

        $this->seed([
            MinimalUserSeeder::class,
            SectorSeeder::class,
        ]);
        $this->authorisationUser();
        $this->adminJwt = $this->getAuthorisationJwt();

        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->adminJwt,
        ];
        $this->authorisationUser(false);
        $this->nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdminJwt,
        ];
    }

    /**
     * Get All Users with success
     *
     * @return void
     */
    public function test_get_all_users_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'firstname',
                    'lastname',
                    'email',
                    'secondary_email',
                    'preferred_email',
                    'provider',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'sector_id',
                    'teams',
                    'notifications',
                    'sector_id',
                    'organisation',
                    'bio',
                    'domain',
                    'link',
                    'orcid',
                    'contact_feedback',
                    'contact_news',
                    'mongo_id',
                    'mongo_object_id',
                    'terms',
                    'notifications',
                    'roles',
                    'hubspot_id',
                ],
            ],
        ]);
        $response->assertStatus(200);
    }

    /**
     * Get All Users with success as non admin
     *
     * @return void
     */
    public function test_non_admin_get_all_users_with_success(): void
    {
        // Create User with a highly unique name
        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'XXXXXXXXXX',
                'lastname' => 'XXXXXXXXXX',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseUser->assertStatus(201);
        $uniqueUserId = $responseUser->decodeResponseJson()['data'];

        $response = $this->json('GET', self::TEST_URL, [], $this->headerNonAdmin);

        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name'
                ],
            ],
        ]);
        $response->assertStatus(200);

        $filterResponse = $this->json('GET', self::TEST_URL . '?filterNames=XXXXX', [], $this->headerNonAdmin);

        // Check the user named XXXXXXXXX is the only match
        $filterUsers = $filterResponse->decodeResponseJson()['data'];
        $this->assertEquals($filterUsers[0]['id'], $uniqueUserId);
    }

    /**
     * Get User by Id with success
     *
     * @return void
     */
    public function test_get_user_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);
        $response->assertJsonStructure([
            'data',
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new User with success
     *
     * @return void
     */
    public function test_add_new_user_with_success(): void
    {
        $countBefore = User::all()->count();

        // Create a notification to be used by the new user
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'secondary_email' => 'just.test.1234567890@test.com',
                'preferred_email' => 'primary',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'provider' => 'open-athens',
                'providerid' => '123456',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
                'notifications' => [$notificationID],
            ],
            $this->header
        );

        $countAfter = User::all()->count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);

        $userId = $response->decodeResponseJson()['data'];
        $user = User::findOrFail($userId);
        $this->assertNotNull($user['providerid']);
    }

    public function test_it_can_update_a_user(): void
    {
        // Create a notification to be used by the new user
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'secondary_email' => 'just.test.1234567890@test.com',
                'preferred_email' => 'primary',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
                'notifications' => [$notificationID],
            ],
            $this->header
        );

        $responseCreate->assertStatus(201);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals($contentCreate['message'], 'created');

        $id = $contentCreate['data'];

        // Finally, update the last entered user to prove functionality
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'secondary_email' => 'just.test.1234567890@test.com',
                'preferred_email' => 'primary',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Updated Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 0,
                'contact_news' => 0,
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
                'notifications' => [$notificationID],
            ],
            $this->header
        );

        $responseUpdate->assertStatus(202)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['organisation'], 'Updated Organisation');
        $this->assertEquals($contentUpdate['data']['contact_feedback'], false);
        $this->assertEquals($contentUpdate['data']['contact_news'], false);
    }

    public function test_it_can_edit_a_user(): void
    {
        // Create a notification to be used by the new user
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // create user (with no notifications initially)
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'secondary_email' => 'just.test.1234567890@test.com',
                'preferred_email' => 'primary',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
                'terms' => true,
            ],
            $this->header
        );

        $responseCreate->assertStatus(201);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals($contentCreate['message'], 'created');

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'secondary_email' => 'just.test.1234567890@test.com',
                'preferred_email' => 'primary',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Updated Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 0,
                'contact_news' => 0,
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
                'terms' => true,
            ],
            $this->header
        );

        $responseUpdate->assertStatus(202)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['organisation'], 'Updated Organisation');
        $this->assertEquals($contentUpdate['data']['contact_feedback'], false);
        $this->assertEquals($contentUpdate['data']['contact_news'], false);

        // edit - change names and notifications
        $responseEdit1 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'firstname' => 'JustE1',
                'lastname' => 'TestE1',
                'notifications' => [$notificationID]
            ],
            $this->header
        );

        $responseEdit1->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['name'], 'JustE1 TestE1');
        $this->assertEquals($contentEdit1['data']['firstname'], 'JustE1');
        $this->assertEquals($contentEdit1['data']['lastname'], 'TestE1');
        $this->assertEquals(
            $contentEdit1['data']['notifications'][0]['notification_type'],
            "applicationSubmitted",
        );

        // edit
        $responseEdit2 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'email' => 'just.test.1234567890@test.com',
            ],
            $this->header
        );

        $responseEdit2->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit2 = $responseEdit2->decodeResponseJson();
        $this->assertEquals($contentEdit2['data']['email'], 'just.test.1234567890@test.com');

        // edit
        $newPasswordEdit3 = 'Passw@rd1!E3';
        $responseEdit3 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'password' => $newPasswordEdit3,
            ],
            $this->header
        );

        $responseEdit3->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $user = User::withTrashed()->where('id', $id)->first();

        if (Hash::check($newPasswordEdit3, $user->password)) {
            $this->assertTrue(true, 'Response was successfully');
        } else {
            $this->assertTrue(false, 'Response was unsuccessfully');
        }
    }

    public function test_non_admin_user_can_edit_themselves(): void
    {
        $nonAdminUser = $this->getUserFromJwt($this->nonAdminJwt);
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $nonAdminUser['id'],
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'secondary_email' => 'just.test.1234567890@test.com',
                'preferred_email' => 'primary',
                'sector_id' => 1,
                'organisation' => 'Updated Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 0,
                'contact_news' => 0,
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
                'terms' => true,
            ],
            $this->headerNonAdmin
        );
        $response->assertStatus(202);
    }

    public function test_non_admin_user_cannot_edit_others(): void
    {
        $nonAdminUser = $this->getUserFromJwt($this->nonAdminJwt);
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $nonAdminUser['id'] + 1,
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'secondary_email' => 'just.test.1234567890@test.com',
                'preferred_email' => 'primary',
                'sector_id' => 1,
                'organisation' => 'Updated Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 0,
                'contact_news' => 0,
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
                'terms' => true,
            ],
            $this->headerNonAdmin
        );
        $response->assertStatus(403);


    }

    public function test_non_admin_user_cannot_read_others(): void
    {
        $nonAdminUser = $this->getUserFromJwt($this->nonAdminJwt);
        $response = $this->json(
            'GET',
            self::TEST_URL . '/' . ($nonAdminUser['id'] - 1),
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(403);
    }

    /**
     * SoftDelete User by Id with success
     *
     * @return void
     */
    public function test_soft_delete_user_with_success(): void
    {
        $id = 1;
        $countBefore = User::where('id', $id)->count();
        $response = $this->json('DELETE', self::TEST_URL . '/' . $id, [], $this->header);

        $countDelete = User::onlyTrashed()->where('id', $id)->count();

        $response->assertStatus(200);

        if ($countBefore && $countDelete) {
            $this->assertTrue(true, 'Response was successfully');
        } else {
            $this->assertTrue(false, 'Response was unsuccessfully');
        }
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
