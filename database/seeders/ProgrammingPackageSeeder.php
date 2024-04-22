<?php

namespace Database\Seeders;

use App\Models\ProgrammingPackage;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'Webpage',
            'Data visualisation',
            'Front End',
            'Natural Langauge Processing',
            'Database querying',
            'Data modelling',
            'ETL',
            'Machine learning',
            'Data anonymization',
        ];

        foreach ($names as $name) {
            ProgrammingPackage::create([
                'name' => $name,
                'enabled' => true,       
            ]);
        }
    }
}
