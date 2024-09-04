<?php

namespace Database\Seeders;

use App\Models\ProgrammingPackage;
use Illuminate\Database\Seeder;

class ProgrammingPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'CI/CD automation',
            'CMS',
            'Data anonymization',
            'Data modelling',
            'Data visualisation',
            'Database querying',
            'ETL',
            'Front End',
            'Machine learning',
            'Natural Langauge Processing',
            'Webpage',
        ];

        foreach ($names as $name) {
            ProgrammingPackage::create([
                'name' => $name,
                'enabled' => true,
            ]);
        }
    }
}
