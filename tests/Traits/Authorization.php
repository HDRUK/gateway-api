<?php

namespace Tests\Traits;

use Hash;
use Config;
use App\Models\Application;
use App\Models\ApplicationHasPermission;
use App\Models\Permission;
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

    public const CFG_NONADMIN2_NAME = 'constants.test.non_admin_2.name';
    public const CFG_NONADMIN2_EMAIL = 'constants.test.non_admin_2.email';
    public const CFG_NONADMIN2_PASSWORD = 'constants.test.non_admin_2.password';
    public const CFG_NONADMIN2_ADMIN = 'constants.test.non_admin_2.is_admin';

    public function authorisationUser(bool $admin = true, int $nonAdminId = 1): bool
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
        } elseif ($nonAdminId === 1) {
            $user = [
                'name' => Config::get(self::CFG_NONADMIN_NAME),
                'firstname' => null,
                'lastname' => null,
                'email' => Config::get(self::CFG_NONADMIN_EMAIL),
                'provider' => Config::get(self::CFG_PROVIDER_SERVICE),
                'password' => Hash::make(Config::get(self::CFG_NONADMIN_PASSWORD)),
                'is_admin' => Config::get(self::CFG_NONADMIN_ADMIN),
            ];
        } else {
            $user = [
                'name' => Config::get(self::CFG_NONADMIN2_NAME),
                'firstname' => null,
                'lastname' => null,
                'email' => Config::get(self::CFG_NONADMIN2_EMAIL),
                'provider' => Config::get(self::CFG_PROVIDER_SERVICE),
                'password' => Hash::make(Config::get(self::CFG_NONADMIN2_PASSWORD)),
                'is_admin' => Config::get(self::CFG_NONADMIN2_ADMIN),
            ];
        }

        $userId = $this->checkUserIfExist($admin, $nonAdminId);

        if ($userId) {
            $this->updateUserIfExist($user, $userId);
        }

        if (!$userId) {
            $this->createNewUser($user);
        }

        return true;
    }

    public function getAuthorisationJwt(bool $admin = true, int $nonAdminId = 1): mixed
    {
        if ($admin) {
            $authData = [
                'email' => Config::get(self::CFG_TEST_EMAIL),
                'password' => Config::get(self::CFG_TEST_PASSWORD),
            ];
        } elseif ($nonAdminId === 1) {
            $authData = [
                'email' => Config::get(self::CFG_NONADMIN_EMAIL),
                'password' => Config::get(self::CFG_NONADMIN_PASSWORD),
            ];
        } else {
            $authData = [
                'email' => Config::get(self::CFG_NONADMIN2_EMAIL),
                'password' => Config::get(self::CFG_NONADMIN2_PASSWORD),
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

    public function createApp(int $teamId, ?int $userId = 1)
    {
        $appId = fake()->regexify('[A-Za-z0-9]{40}');
        $clientId = fake()->regexify('[A-Za-z0-9]{40}');
        $clientSecret = Hash::make($appId . ':' . $clientId . ':' . env('APP_AUTH_PRIVATE_SALT') . ':' . env('APP_AUTH_PRIVATE_SALT_2'));

        $app = Application::create([
            'name' => fake()->text(10),
            'app_id' => $appId,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'description' => fake()->text(),
            'team_id' => $teamId,
            'user_id' => $userId,
            'enabled' => 1,
        ]);

        $perms = Permission::whereIn('name', [
            'datasets.create',
            'datasets.read',
            'datasets.update',
            'datasets.delete',
            'dur.create',
            'dur.read',
            'dur.update',
            'dur.delete',
            'tools.create',
            'tools.read',
            'tools.update',
            'tools.delete',
        ])->get();

        foreach ($perms as $perm) {
            ApplicationHasPermission::firstOrCreate([
                'application_id' => $app->id,
                'permission_id' => $perm->id,
            ]);
        }

        return [
            'Accept' => 'application/json',
            'x-application-id' => $app->app_id,
            'x-client-id' => $app->client_id,
        ];
    }

    protected function checkUserIfExist(bool $admin, int $nonAdminId = 1): mixed
    {
        if ($admin) {
            $user = User::where(['email' => Config::get(self::CFG_TEST_EMAIL)])->first();
        } elseif ($nonAdminId === 1) {
            $user = User::where(['email' => Config::get(self::CFG_NONADMIN_EMAIL)])->first();
        } else {
            $user = User::where(['email' => Config::get(self::CFG_NONADMIN2_EMAIL)])->first();
        }

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
