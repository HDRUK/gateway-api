<?php

namespace Database\Seeders;

use App\Models\CommandConfig;
use Illuminate\Database\Seeder;

class FAIRShareCommandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonConfig = '{
            "steps": [
                {
                    "type": "auth",
                    "url": "https://api.fairsharing.org/users/sign_in",
                    "payload": {
                        "user": {
                            "login": "loki.sinclair@hdruk.ac.uk",
                            "password": "Challenge12Havoc!_"
                        }
                    },
                    "token_response_key": "jwt"
                },
                {
                    "type": "run",
                    "url": "https://api.fairsharing.org/collections",
                    "method": "get",
                    "auth_type": "bearer"
                }
            ]
        }';

        CommandConfig::create([
            'ident' => 'fs_scraper',
            'config' => $jsonConfig,
            'enabled' => 1,
        ]);
    }
}
