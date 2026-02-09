<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\WidgetSetting;
use Illuminate\Database\Seeder;

class TeamWidgetSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WidgetSetting::truncate();

        $teamName = 'NHS England Secure Data Environment';

        $team = Team::where('name', 'like', '%' . $teamName . '%')->first();

        if (!is_null($team)) {
            WidgetSetting::create([
                'team_id' => $team->id,
                'colours' => [
                    'primary' => '#005EB8',
                    'seconday' => '#006747',
                    'neutral' => '#E8EDEE',
                ]
            ]);
        }
    }
}
