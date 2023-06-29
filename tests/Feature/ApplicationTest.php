<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL = '/api/v1/applications';

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
     * Get All Applications with success
     * 
     * @return void
     */
    public function test_get_all_applications_with_success(): void
    {
        $response = $this->json(
            'GET', 
            self::TEST_URL, 
            [], 
            $this->header,
        );

        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'app_id',
                    'client_id',
                    'logo',
                    'description',
                    'team_id',
                    'user_id',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'permissions',
                    'tags',
                    'team',
                    'user',
                ],
            ],
            'current_page',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);
    }
}
