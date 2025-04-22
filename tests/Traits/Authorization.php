<?php

namespace Tests\Traits;

use Hash;
use Config;
use App\Models\User;
use App\Http\Controllers\JwtController;

trait Authorization
{
    public const CFG_TEST_NAME = 'constants.test_superuser.name';
    public const CFG_TEST_EMAIL = 'constants.test_superuser.email';
    public const CFG_TEST_PASSWORD = 'constants.test_superuser.password';
    public const CFG_TEST_ADMIN = 'constants.test_superuser.is_admin';
    public const CFG_PROVIDER_SERVICE = 'constants.provider.service';

    public function createSuperAdminUser(): bool
    {
        $user = [
            'name' => Config::get(self::CFG_TEST_NAME),
            'firstname' => null,
            'lastname' => null,
            'email' => Config::get(self::CFG_TEST_EMAIL),
            'provider' => Config::get(self::CFG_PROVIDER_SERVICE),
            'password' => Hash::make(Config::get(self::CFG_TEST_PASSWORD)),
            'is_admin' => Config::get(self::CFG_TEST_ADMIN),
        ];

        $userId = $this->checkUserIfExist();

        if ($userId) {
            $this->updateUserIfExist($user, $userId);
        }

        if (!$userId) {
            $this->createNewUser($user);
        }

        return true;
    }

    public function getSuperUserJwt(): mixed
    {
        $authData = [
            'email' => Config::get(self::CFG_TEST_EMAIL),
            'password' => Config::get(self::CFG_TEST_PASSWORD),
        ];

        $response = $this->json('POST', '/api/v1/auth', $authData, ['Accept' => 'application/json']);

        return $response['access_token'];
    }

    public function getUserFromJwt(string $jwt): mixed
    {
        $jwtController = new JwtController();
        $jwtController->setJwt($jwt);
        $payloadJwt = $jwtController->decode();
        $userJwt = $payloadJwt['user'];

        return $userJwt;
    }

    protected function checkUserIfExist(): mixed
    {
        $user = User::where(['email' => Config::get(self::CFG_TEST_EMAIL)])->first();

        return $user !== null ? $user->id : null;
    }

    protected function updateUserIfExist($user, $userId): int
    {
        return User::where('id', $userId)->update($user);
    }

    protected function createNewUser($user): User
    {
        return User::create($user);
    }
}
