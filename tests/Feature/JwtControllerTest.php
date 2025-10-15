<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Tests\Traits\MockExternalApis;
use App\Http\Controllers\JwtController;
use Database\Seeders\MinimalUserSeeder;


class JwtControllerTest extends TestCase
{
    
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * generate jwt token
     */
    public function test_generate_jwt(): void
    {
        $jwtClass = new JwtController();
        $userId = User::all()->random()->id;
        $jwt = $jwtClass->generateToken($userId);

        $this->assertIsString($jwt);
    }

    public function test_generate_and_check_jwt_payload()
    {
        $jwtClass = new JwtController();
        $userId = User::all()->random()->id;
        $jwt = $jwtClass->generateToken($userId);
        $jwtClass->setJwt($jwt);
        $jwtDecoded = $jwtClass->decode();

        $isValidJwt = $jwtClass->isValid();

        $this->assertEquals(true, $isValidJwt);
        $this->assertEquals($jwtDecoded['user']['id'], $userId);
    }
}
