<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\TeamHasUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeamHasUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 100; $count++) {
            $teamId = Team::all()->random()->id;
            $userId = User::where('id', '!=', 1)->inRandomOrder()->first()->id;

            $teamHasUser = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            if (!$teamHasUser) {
                TeamHasUser::create([
                    'team_id' => $teamId,
                    'user_id' => $userId,
                ]);
            }
        }
    }
}
