<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $globalFeatures = [
            ['name' => 'SDEConciergeServiceEnquiry', 'value' => 'true'],
            ['name' => 'Aliases', 'value' => 'true'],
            ['name' => 'NhsSdeApplicationsEnabled', 'value' => 'false'],
            ['name' => 'Widgets', 'value' => 'false'],
            ['name' => 'RQuest', 'value' => 'true'],
            ['name' => 'CohortDiscoveryService', 'value' => 'false'],
        ];

        foreach ($globalFeatures as $feature) {
            Feature::updateOrCreate(
                ['name' => $feature['name'], 'scope' => '__laravel_null'],
                ['value' => $feature['value'], 'updated_at' => $now]
            );
        }
    }
}
