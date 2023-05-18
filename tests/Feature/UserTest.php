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
        $countBefore = User::all()->count();
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $this->assertCount($countBefore, $response['data']);
        // var_dump($response);
        $response->assertJsonStructure([
            'message',
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

        $this->assertCount(1, $response['data']);
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
                ]
            ]
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
                'contact_feedback' => 1,
                'contact_news' => 1,
            ],
            $this->header
        );

        $countAfter = User::all()->count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
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
