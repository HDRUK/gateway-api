<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Tests\Traits\Authorization;

class UserOrganisationTest extends TestCase
{
    use Authorization;

    public const TEST_URL = '/api/v1/users/organisations';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];
    }

    /**
     * Get All Orgs with success
     *
     * @return void
     */
    public function test_get_all_user_organisations_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertJsonStructure([
            'message',
            'data'
        ]);
        $response->assertStatus(200);
    }

    /**
     * Get All Orgs fails with non admin
     *
     * @return void
     */
    public function test_get_all_user_organisations_fails_with_non_admin(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->headerNonAdmin);

        $response->assertJsonStructure(
            [
                'message',
                'details'
            ],
        );
        $response->assertStatus(403);
    }


    /**
     * Get All Orgs succeeds with cohort admin
     *
     * @return void
     */
    public function test_get_all_user_organisations_success_with_cohort_admin_role(): void
    {
        // authenticate the seeded cohort admin user
        $authData = [
            'email' => 'hdrcohortadmin@gmail.com',
            'password' => 'Flood15?Voice',
        ];
        $authResponse = $this->json('POST', '/api/v1/auth', $authData, ['Accept' => 'application/json']);
        $cohortAdminHeader = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $authResponse['access_token'],
        ];

        $response = $this->json('GET', self::TEST_URL, [], $cohortAdminHeader);

        $response->assertJsonStructure([
            'message',
            'data'
        ]);
        $response->assertStatus(200);
    }
}
