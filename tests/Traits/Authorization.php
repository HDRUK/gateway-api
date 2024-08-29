<?php

namespace Tests\Traits;

use Hash;
use Config;
use App\Models\User;
use App\Http\Controllers\JwtController;

trait Authorization
{
    public const CFG_TEST_NAME = 'constants.test.user.name';
    public const CFG_TEST_EMAIL = 'constants.test.user.email';
    public const CFG_TEST_PASSWORD = 'constants.test.user.password';
    public const CFG_TEST_ADMIN = 'constants.test.user.is_admin';
    public const CFG_PROVIDER_SERVICE = 'constants.provider.service';

    public const CFG_NONADMIN_NAME = 'constants.test.non_admin.name';
    public const CFG_NONADMIN_EMAIL = 'constants.test.non_admin.email';
    public const CFG_NONADMIN_PASSWORD = 'constants.test.non_admin.password';
    public const CFG_NONADMIN_ADMIN = 'constants.test.non_admin.is_admin';

    public function authorisationUser(bool $admin = true): bool
    {
        if ($admin) {
            $user = [
                'name' => Config::get(self::CFG_TEST_NAME),
                'firstname' => null,
                'lastname' => null,
                'email' => Config::get(self::CFG_TEST_EMAIL),
                'provider' => Config::get(self::CFG_PROVIDER_SERVICE),
                'password' => Hash::make(Config::get(self::CFG_TEST_PASSWORD)),
                'is_admin' => Config::get(self::CFG_TEST_ADMIN),
            ];
        } else {
            $user = [
                'name' => Config::get(self::CFG_NONADMIN_NAME),
                'firstname' => null,
                'lastname' => null,
                'email' => Config::get(self::CFG_NONADMIN_EMAIL),
                'provider' => Config::get(self::CFG_PROVIDER_SERVICE),
                'password' => Hash::make(Config::get(self::CFG_NONADMIN_PASSWORD)),
                'is_admin' => Config::get(self::CFG_NONADMIN_ADMIN),
            ];
        }

        $userId = $this->checkUserIfExist($admin);

        if ($userId) {
            $this->updateUserIfExist($user, $userId);
        }

        if (!$userId) {
            $this->createNewUser($user);
        }

        return true;
    }

    public function getAuthorisationJwt(bool $admin = true): mixed
    {
        if ($admin) {
            $authData = [
                'email' => Config::get(self::CFG_TEST_EMAIL),
                'password' => Config::get(self::CFG_TEST_PASSWORD),
            ];
        } else {
            $authData = [
                'email' => Config::get(self::CFG_NONADMIN_EMAIL),
                'password' => Config::get(self::CFG_NONADMIN_PASSWORD),
            ];
        }

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

    protected function checkUserIfExist(bool $admin): mixed
    {
        if ($admin) {
            $user = User::where(['email' => Config::get(self::CFG_TEST_EMAIL)])->first();
        } else {
            $user = User::where(['email' => Config::get(self::CFG_NONADMIN_EMAIL)])->first();
        }

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
