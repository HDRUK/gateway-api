<?php

namespace Tests\Feature;

use Hash;
use App\Models\User;
use Tests\TestCase;
use Tests\Traits\Authorization;
// use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL = '/api/v1/users';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
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
                    'providerid',
                    'provider',
                    'created_at',
                    'updated_at',
                    'deleted_at',
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
                ],
            ],   
        ]);
        $response->assertStatus(200);
    }

    /**
     * Get All Tag with no success
     * 
     * @return void
     */
    public function test_get_all_users_and_generate_exception(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], []);
        $response->assertStatus(401);
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

        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => 75697342,
                'contact_feedback' => 1,
                'contact_news' => 1, 
                'mongo_id' => 1234567,
            ],
            $this->header
        );

        $countAfter = User::all()->count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
    }

    public function test_it_can_update_a_user(): void
    {
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => 75697342,
                'contact_feedback' => 1,
                'contact_news' => 1, 
                'mongo_id' => 1234567,
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
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Updated Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => 75697342,
                'contact_feedback' => 0,
                'contact_news' => 0, 
                'mongo_id' => 1234567,
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
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => 75697342,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234567,
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
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Updated Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => 75697342,
                'contact_feedback' => 0,
                'contact_news' => 0,
                'mongo_id' => 1234567,
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

        // edit
        $responseEdit1 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'firstname' => 'JustE1',
                'lastname' => 'TestE1',
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
}
