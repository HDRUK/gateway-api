<?php

namespace Database\Beta;

use App\Models\Sector;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SectorBetaDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = [
            'NHS',
            'Industry',
            'Academia',
            'Public',
            'Charity/Non-profit',
            'Not specified',
        ];

        foreach ($sectors as $sector) {
            Sector::create([
                'name' => $sector,
                'enabled' => true,
            ]);
        }
    }
}
