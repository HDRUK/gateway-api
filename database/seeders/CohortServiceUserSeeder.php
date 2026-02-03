<?php

namespace Database\Seeders;

use App\Models\OauthClient;
use App\Models\User;
use Config;
use Database\Seeders\Traits\HelperFunctions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CohortServiceUserSeeder extends Seeder
{
    use HelperFunctions;

    public function run(): void
    {
        $email = Config::get('services.cohort_discovery.service_account');
        $clientName = 'cohort-discovery-oauth-client';

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            OauthClient::where('user_id', $existingUser->id)
                ->where('name', $clientName)
                ->delete();

            $existingUser->delete();
        }

        $this->createUser(
            'HDRUK',
            'Cohort-Service-User',
            $email,
            '$2y$10$qmXzkOCukyMCXwYrSuNgE.S7MMkswr7/vIoENJngxdn5kdeiwCcyu',
            true,
            [
                'hdruk.superadmin', // needed?
            ]
        );

        $user = User::where('email', $email)->firstOrFail();
        OauthClient::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => $clientName,
            'secret' => Hash::make(Str::random(40)),
            'provider' => null,
            'redirect' => Config::get('services.cohort_discovery.auth_url'),
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
