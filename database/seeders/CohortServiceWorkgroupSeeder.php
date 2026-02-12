<?php

namespace Database\Seeders;

use App\Models\Workgroup;
use Illuminate\Database\Seeder;

class CohortServiceWorkgroupSeeder extends Seeder
{
    private array $workgroups = [
        'non-uk-industry',
        'non-uk-research',
        'uk-industry',
        'uk-research',
        'nhs-sde',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->workgroups as $w) {
            Workgroup::firstOrCreate(
                ['name' => $w],
                []
            );
        }
    }
}
