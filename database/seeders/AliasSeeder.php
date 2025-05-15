<?php

namespace Database\Seeders;

use App\Models\Alias;
use App\Models\Team;
use App\Models\TeamHasAlias;
use Illuminate\Database\Seeder;

class AliasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // aliases table
        for ($i = 1; $i <= 20; $i++) {
            do {
                $alias = fake()->unique()->word();
            } while (strlen($alias) < 3);


            $checkAlias = Alias::where('name', $alias)->first();
            if (!is_null($checkAlias)) {
                continue;
            }

            Alias::create([
                'name' => $alias,
            ]);
        }

        // team_has_aliases table
        $teams = Team::all();
        foreach ($teams as $team) {
            $alias = Alias::inRandomOrder()->first();

            $checkTeamAlias = TeamHasAlias::where([
                'team_id' => $team->id,
                'alias_id' => $alias->id,
            ])->first();
            if (is_null($checkTeamAlias)) {
                TeamHasAlias::create([
                    'team_id' => $team->id,
                    'alias_id' => $alias->id,
                ]);
            } else {
                continue;
            }
        }
    }
}
