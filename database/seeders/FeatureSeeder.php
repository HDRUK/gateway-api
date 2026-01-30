<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Feature::truncate();

        $now = Carbon::now();

        // Seed features with global scope
        $globalFeatures = [
            ['name' => 'SDEConciergeServiceEnquiry', 'value' => 'true'],
            ['name' => 'Aliases', 'value' => 'true'],
            ['name' => 'NhsSdeApplicationsEnabled', 'value' => 'false'],
            ['name' => 'Widgets', 'value' => 'false'],
            ['name' => 'RQuest', 'value' => 'true'],
            ['name' => 'CohortDiscoveryService', 'value' => 'false'],
        ];

        foreach ($globalFeatures as $feature) {
            Feature::create([
                'name' => $feature['name'],
                'scope' => '__laravel_null',
                'value' => $feature['value'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->newLine();
        $this->command->info('All feature flags seeded successfully!');
        $this->command->newLine();
    }
}
