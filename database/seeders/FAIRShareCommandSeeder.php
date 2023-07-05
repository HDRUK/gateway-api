<?php

namespace Database\Seeders;

use App\Models\CommandConfig;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                    "auth_type": "Bearer"
                },
                {
                    "type": "run",
                    "url": "https://api.fairsharing.org/collections",
                    "method": "get"
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
