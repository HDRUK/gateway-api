<?php

namespace Database\Demo;

use Exception;

use App\Models\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class UserAddDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usersMongoId = [
            // HEALTH DATA RESEARC
            '64805ed24fb6ec2d8ae95c03',
            '5fb3a0962a1e746fd5652b68',
            '62d6d6726cbddb291c32cd35',
            '62fd8637b335ed01035e3ad5',

            // SAIL
            '636a672c8ff2020553bd5903',
            '62a0746c85e29c511c8b312c',
            '5ed664837e6ed617c4437d23',
            '61b7553fd870780ec9626666',

            // PUBLIC HEALTH SCOTLAND
            '622a2c4219d9bcf86f5bcfa6',
            '615465c27c92dd6db92a146c',
            '6128f92f579e717d8f120a54',
            '608c107c8c89a2a492406773',
        ];

        for ($i = 0; $i < 12; $i++) {
            try {
                $firstName = fake()->firstName();
                $lastName = fake()->lastName();
                $payload = [
                    'provider' => 'google',
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'email' => "{$firstName}.{$lastName}@example.com",
                    'password' => 'H@r@pA1b',
                    'organisation' => fake()->company(),
                    'sector_id' => 1,
                    'contact_feedback' => 1,
                    'contact_news' => 1,
                    'bio' => fake()->word(),
                    'domain' => fake()->word(),
                    'link' => fake()->url(),
                    'orcid' => 'https://orcid.org/' . fake()->randomNumber(8, true),
                    'mongo_id' => fake()->randomNumber(8, true),
                    'mongo_object_id' => $usersMongoId[$i],
                    'is_admin' => false,
                    'terms' => fake()->numberBetween(0, 1),
                ];

                User::create($payload);
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }

        }
    }
}
