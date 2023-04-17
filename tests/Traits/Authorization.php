<?php

namespace Tests\Traits;

use Hash;
use Config;
use App\Models\User;

trait Authorization
{
    const CFG_TEST_NAME = 'constants.test.user.name';
    const CFG_TEST_EMAIL = 'constants.test.user.email';
    const CFG_TEST_PASSWORD = 'constants.test.user.password';
    const CFG_PROVIDER_SERVICE = 'constants.provider.service';

    public function authorisationUser(): bool
    {
        $user = [
            'name' => Config::get(self::CFG_TEST_NAME),
            'firstname' => null,
            'lastname' => null,
            'email' => Config::get(self::CFG_TEST_EMAIL),
            'provider' => Config::get(self::CFG_PROVIDER_SERVICE),
            'password' => Hash::make(Config::get(self::CFG_TEST_PASSWORD)),
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

    public function getAuthorisationJwt() : mixed
    {
        $authData = [
            'email' => Config::get(self::CFG_TEST_EMAIL),
            'password' => Config::get(self::CFG_TEST_PASSWORD),
        ];
        $response = $this->json('POST', '/api/v1/auth', $authData, ['Accept' => 'application/json']);
        
        return $response['access_token'];
    }

    protected function checkUserIfExist(): mixed
    {
        $user = User::where(['email' => Config::get(self::CFG_TEST_EMAIL)])->first();

        return $user !== null ? $user->id : null;
    }

    protected function updateUserIfExist($user, $userId): User
    {
        return User::where('id', $userId)->update($user);
    }

    protected function createNewUser($user): User
    {
        return User::create($user);
    }
}