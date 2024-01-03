<?php

namespace Database\Demo;

use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeamUserRoleDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** 
         * we have team now:
         * | id   | name                     | mongo_object_id            | user ids       |
         * | ==== | ======================== | ========================== | ============== |
         * |  1   | 'HEALTH DATA RESEARCH'   | '5f7b1a2bce9f65e6ed83e7da' | 3, 4, 5, 6     |
         * | ---- | ------------------------ | -------------------------- | -------------- |
         * |  2   | 'SAIL'                   | '5f3f98068af2ef61552e1d75' | 7, 8, 9, 10    | 
         * | ---- | ------------------------ | -------------------------- | -------------- |
         * |  3   | 'PUBLIC HEALTH SCOTLAND' | '5f8992a97150a1b050be0712' | 11, 12, 13, 14 |
         * | ==== | ======================== | ========================== | ============== |
         */

        $userTeams = [
            1 => [
                [
                    'user_id' => 3,
                    'roles' => ['custodian.team.admin'],
                ],
                [
                    'user_id' => 4,
                    'roles' => ['developer'],
                ],
                [
                    'user_id' => 5,
                    'roles' => ['custodian.metadata.manager'],
                ],
                [
                    'user_id' => 6,
                    'roles' => ['custodian.dar.manager'],
                ],
            ],
            2 => [
                [
                    'user_id' => 7,
                    'roles' => ['custodian.team.admin'],
                ],
                [
                    'user_id' => 8,
                    'roles' => ['developer'],
                ],
                [
                    'user_id' => 9,
                    'roles' => ['custodian.metadata.manager'],
                ],
                [
                    'user_id' => 10,
                    'roles' => ['custodian.dar.manager'],
                ],
            ],
            3 => [
                [
                    'user_id' => 11,
                    'roles' => ['custodian.team.admin'],
                ],
                [
                    'user_id' => 12,
                    'roles' => ['developer'],
                ],
                [
                    'user_id' => 13,
                    'roles' => ['custodian.metadata.manager'],
                ],
                [
                    'user_id' => 14,
                    'roles' => ['custodian.dar.manager'],
                ],
            ],
        ];
        $authorisation = AuthorisationCode::first();

        foreach ($userTeams as $teamId => $users) {
            foreach ($users as $user) {
                $payload = [
                    'userId' => $user['user_id'],
                    'roles' => $user['roles'],
                ];
                $url = env('APP_URL') . '/api/v1/teams/' . $teamId . '/users?email=false';
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $authorisation->jwt,
                    'Content-Type' => 'application/json',
                ])->post($url, $payload);
            }
        }
    }
}
