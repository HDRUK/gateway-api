<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UserHasRole;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class UserRoleTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    public function test_assing_roles_to_users_with_success(): void
    {
        // Create new user
        $responseCreateUser = $this->json(
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
            ],
            $this->header
        );
        $responseCreateUser->assertStatus(201);
        $uniqueUserId = $responseCreateUser->decodeResponseJson()['data'];

        // assign roles to user
        $url = '/api/v1/users/' . $uniqueUserId . '/roles';
        $responseUserRole = $this->json(
            'POST',
            $url,
            [
                'roles' => [
                    'custodian.metadata.manager',
                    'metadata.editor',
                    'dar.reviewer'
                ]
            ],
            $this->header
        );
        $responseUserRole->assertStatus(201);
    }

    public function test_edit_roles_to_users_with_success(): void
    {
        // Create new user
        $responseCreateUser = $this->json(
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
            ],
            $this->header
        );
        $responseCreateUser->assertStatus(201);
        $uniqueUserId = $responseCreateUser->decodeResponseJson()['data'];

        // assign roles to user
        $responseUserRole = $this->json(
            'POST',
            '/api/v1/users/' . $uniqueUserId . '/roles',
            [
                'roles' => [
                    'custodian.metadata.manager',
                    'metadata.editor',
                    'dar.reviewer'
                ]
            ],
            $this->header
        );
        $responseUserRole->assertStatus(201);

        // update roles to user
        $responseUpdateUserRole = $this->json(
            'PATCH',
            '/api/v1/users/' . $uniqueUserId . '/roles',
            [
                'roles' => [
                    'custodian.metadata.manager' => false,
                ]
            ],
            $this->header
        );
        $responseUpdateUserRole->assertStatus(200);

        $countRoles = UserHasRole::where('user_id', $uniqueUserId)->count();

        $this->assertTrue((int) $countRoles === 2);
    }

    public function test_delete_roles_to_users_with_success(): void
    {
        // Create new user
        $responseCreateUser = $this->json(
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
            ],
            $this->header
        );
        $responseCreateUser->assertStatus(201);
        $uniqueUserId = $responseCreateUser->decodeResponseJson()['data'];

        // assign roles to user
        $responseUserRole = $this->json(
            'POST',
            '/api/v1/users/' . $uniqueUserId . '/roles',
            [
                'roles' => [
                    'custodian.metadata.manager',
                    'metadata.editor',
                    'dar.reviewer'
                ]
            ],
            $this->header
        );
        $responseUserRole->assertStatus(201);

        // update roles to user
        $responseUpdateUserRole = $this->json(
            'PATCH',
            '/api/v1/users/' . $uniqueUserId . '/roles',
            [
                'roles' => [
                    'custodian.metadata.manager' => false,
                ]
            ],
            $this->header
        );
        $responseUpdateUserRole->assertStatus(200);

        $countRoles = UserHasRole::where('user_id', $uniqueUserId)->count();

        $this->assertTrue((int) $countRoles === 2);

        // delete roles to user
        $responseUpdateUserRole = $this->json(
            'DELETE',
            '/api/v1/users/' . $uniqueUserId . '/roles',
            [],
            $this->header
        );
        $responseUpdateUserRole->assertStatus(200);

        $countRoles = UserHasRole::where('user_id', $uniqueUserId)->count();

        $this->assertTrue((int) $countRoles === 0);
    }

    public function test_cannot_grant_hdruk_superadmin_to_a_user_with_a_non_allow_listed_email_domain(): void
    {
        $responseCreateUser = $this->json(
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
            ],
            $this->header
        );
        $responseCreateUser->assertStatus(201);
        $uniqueUserId = $responseCreateUser->decodeResponseJson()['data'];

        $response = $this->json(
            'POST',
            '/api/v1/users/' . $uniqueUserId . '/roles',
            [
                'roles' => [
                    'hdruk.superadmin',
                ]
            ],
            $this->header
        );

        $response->assertStatus(500);
        $this->assertSame(0, UserHasRole::where('user_id', $uniqueUserId)->count());
    }

    public function test_a_newly_marked_privileged_role_is_enforced_without_a_code_change(): void
    {
        $customRoleName = 'test.custom.privileged';
        Role::create(['name' => $customRoleName, 'enabled' => true, 'is_privileged' => true]);

        $responseCreateUser = $this->json(
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
            ],
            $this->header
        );
        $responseCreateUser->assertStatus(201);
        $uniqueUserId = $responseCreateUser->decodeResponseJson()['data'];

        $response = $this->json(
            'POST',
            '/api/v1/users/' . $uniqueUserId . '/roles',
            [
                'roles' => [
                    $customRoleName,
                ]
            ],
            $this->header
        );

        $response->assertStatus(500);
        $this->assertSame(0, UserHasRole::where('user_id', $uniqueUserId)->count());
    }
}
