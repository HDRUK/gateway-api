<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
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
