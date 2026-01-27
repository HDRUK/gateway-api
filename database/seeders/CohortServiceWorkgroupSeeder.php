<?php

namespace Database\Seeders;

use App\Models\Workgroup;
use Illuminate\Database\Seeder;

class CohortServiceWorkgroupSeeder extends Seeder
{
    private array $workgroups = [
        'admin',
        'default',
        'custodian-admin',
        'custodian-tester',
        'non-uk-industry',
        'non-uk-research',
        'other',
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
            Workgroup::create([
                'name' => $w,
            ]);
        }
    }
}
