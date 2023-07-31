<?php

namespace Tests\Feature;

use Tests\TestCase;
use Carbon\CarbonImmutable;
use App\Http\Controllers\JwtController;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JwtControllerTest extends TestCase
{
    /**
     * generate jwt token
     */
    public function test_generate_jwt(): void
    {
        $jwtClass = new JwtController();

        $currentTime = CarbonImmutable::now();
        $expireTime = $currentTime->addSeconds(env('JWT_EXPIRATION'));

        $userName = fake()->firstName() . ' ' . fake()->lastName;
        $userId = mt_rand(100, 200);


        $userClaims = [
            'id' => (string) mt_rand(100, 200),
            'name' => $userId,
            'email' => fake()->unique()->safeEmail(),
        ];

        $arrayClaims = [
            'iss' => (string) env('APP_URL'),
            'sub' => (string) $userName,
            'aud' => (string) env('APP_NAME'),
            'iat' => (string) strtotime($currentTime),
            'nbf' => (string) strtotime($currentTime),
            'exp' => (string) strtotime($expireTime),
            'jti' => (string) env('JWT_SECRET'),
            'user' => $userClaims,
        ];

        $jwtClass->setPayload($arrayClaims);
        $jwt = $jwtClass->create();

        $this->assertIsString($jwt);
    }

    public function test_generate_and_check_jwt_payload()
    {
        // generate a jwt
        $jwtClass = new JwtController();

        $currentTime = CarbonImmutable::now();
        $expireTime = $currentTime->addSeconds(env('JWT_EXPIRATION'));

        $userName = fake()->firstName() . ' ' . fake()->lastName;
        $userId = mt_rand(100, 200);
        $userEmail = fake()->unique()->safeEmail();

        $userClaims = [
            'id' => (string) $userId,
            'name' => $userName,
            'email' => $userEmail,
        ];

        $arrayClaims = [
            'iss' => (string) env('APP_URL'),
            'sub' => (string) $userName,
            'aud' => (string) env('APP_NAME'),
            'iat' => (string) strtotime($currentTime),
            'nbf' => (string) strtotime($currentTime),
            'exp' => (string) strtotime($expireTime),
            'jti' => (string) env('JWT_SECRET'),
            'user' => $userClaims,
        ];

        $jwtClass->setPayload($arrayClaims);
        $jwt = $jwtClass->create();

        $this->assertIsString($jwt);

        // decode the jwt
        $jwtClass->setJwt($jwt);
        $jwtDecoded = $jwtClass->decode();

        $this->assertArrayHasKey('iss', $jwtDecoded);
        $this->assertArrayHasKey('sub', $jwtDecoded);
        $this->assertArrayHasKey('aud', $jwtDecoded);
        $this->assertArrayHasKey('iat', $jwtDecoded);
        $this->assertArrayHasKey('nbf', $jwtDecoded);
        $this->assertArrayHasKey('exp', $jwtDecoded);
        $this->assertArrayHasKey('jti', $jwtDecoded);
        $this->assertArrayHasKey('user', $jwtDecoded);
        $this->assertArrayHasKey('id', $jwtDecoded['user']);
        $this->assertArrayHasKey('name', $jwtDecoded['user']);
        $this->assertArrayHasKey('email', $jwtDecoded['user']);

        $this->assertEquals($jwtDecoded['user']['id'], $userId);
        $this->assertEquals($jwtDecoded['user']['name'], $userName);
        $this->assertEquals($jwtDecoded['user']['email'], $userEmail);
    }
}
