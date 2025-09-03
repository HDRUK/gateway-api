<?php

namespace Database\Seeders;

use App\Models\OauthClient;
use App\Models\User;
use Config;
use Illuminate\Database\Seeder;
use Database\Seeders\Traits\HelperFunctions;
use Illuminate\Support\Facades\Hash;
use Str;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CohortServiceUserSeeder extends Seeder
{
    use HelperFunctions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createUser(
            'HDRUK',
            'Cohort-Service-User',
            Config::get('services.cohort_discovery.service_account'),
            '$2y$10$qmXzkOCukyMCXwYrSuNgE.S7MMkswr7/vIoENJngxdn5kdeiwCcyu',
            true,
            [
                'hdruk.superadmin', // needed?
            ]
        );
        $user = User::where('email', Config::get('services.cohort_discovery.service_account'))->first();

        OauthClient::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'cohort-discovery-oauth-client',
            'secret' => Hash::make(Str::random(40)),
            'provider' => null,
            'redirect' => Config::get('services.cohort_discovery.init_url'),
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
