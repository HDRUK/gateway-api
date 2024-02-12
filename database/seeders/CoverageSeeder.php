<?php

namespace Database\Seeders;

use App\Models\Coverage;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoverageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coverage = [
            'England',
            'Northern Ireland',
            'Scotland',
            'Wales',
            'Rest of the world',
        ];

        foreach ($coverage as $c) {
            Coverage::create([
                'name' => $c,
                'enabled' => true,       
            ]);
        }
    }
}
