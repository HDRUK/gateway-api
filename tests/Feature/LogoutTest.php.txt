<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\Authorization;

class LogoutTest extends TestCase
{
    use Authorization;

    public const USERS_URL = '/api/v1/users';
    public const LOGOUT_URL = '/api/v1/logout';
    public const AUTH_URL = '/api/v1/auth';

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

    // LS - Rmoved for now - as the whole login process is a bit strange and
    // needs looking at properly.
    //
    // public function test_create_jwt_with_success(): void
    // {
    //     // new user
    //     $responseNewUser = $this->json(
    //         'POST',
    //         self::USERS_URL,
    //         [
    //             'firstname' => 'Just',
    //             'lastname' => 'Test',
    //             'email' => 'just.test.123456789@test.com',
    //             'password' => 'Passw@rd1!',
    //             'sector_id' => 1,
    //             'contact_feedback' => 1,
    //             'contact_news' => 1,
    //             'organisation' => 'Updated Organisation',
    //             'bio' => 'Test Biography',
    //             'domain' => 'https://testdomain.com',
    //             'link' => 'https://testlink.com/link',
    //             'orcid' => 75697342,
    //         ],
    //         $this->header
    //     );
    //     $responseNewUser->assertStatus(201);

    //     // login
    //     $responseLogin = $this->json(
    //         'POST',
    //         self::AUTH_URL . '/',
    //         [
    //             'email' => 'just.test.123456789@test.com',
    //             'password' => 'Passw@rd1!',
    //         ],
    //         []
    //     );
    //     $responseLogin->assertStatus(200);
    //     $jwt = $responseLogin['access_token'];

    //     // logout
    //     $responseLogout = $this->json(
    //         'POST',
    //         self::LOGOUT_URL,
    //         [],
    //         [
    //             'Accept' => 'application/json',
    //             'Authorization' => 'Bearer ' . $jwt,
    //         ]
    //     );
    //     $responseLogout->assertStatus(302);

    //     // login
    //     $responseUsers = $this->json('GET', self::USERS_URL, [],[
    //         'Accept' => 'application/json',
    //         'Authorization' => 'Bearer ' . $jwt,
    //     ]);
    //     $responseUsers->assertStatus(401);
    // }
}
