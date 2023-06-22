<?php

namespace Tests\Feature;

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
                'orcid' => 'https://orcid.org/75697342',
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
                'orcid' => 'https://orcid.org/75697342',
                'contact_feedback' => 1,
                'contact_news' => 1, 
                'mongo_id' => 1234567,
            ],
            $this->header
        );

        $response->assertStatus(201);      
        
        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'],
            'created',
        );

        // Finally, update the last entered user to prove functionality
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $content['data'],
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
                'orcid' => 'https://orcid.org/75697342',
                'contact_feedback' => 0,
                'contact_news' => 0, 
                'mongo_id' => 1234567,
            ],
            $this->header
        );

        $response->assertStatus(202)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['organisation'], 'Updated Organisation');
        $this->assertEquals($content['data']['contact_feedback'], false);
        $this->assertEquals($content['data']['contact_news'], false);
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
