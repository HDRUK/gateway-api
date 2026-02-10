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
        $now = Carbon::now();

        $globalFeatures = [
            ['name' => 'SDEConciergeServiceEnquiry', 'value' => 'true'],
            ['name' => 'Aliases', 'value' => 'true'],
            ['name' => 'NhsSdeApplicationsEnabled', 'value' => 'false'],
            ['name' => 'Widgets', 'value' => 'false'],
            ['name' => 'RQuest', 'value' => 'true'],
            ['name' => 'CohortDiscoveryService', 'value' => 'false'],
        ];

        $created = 0;
        $existing = 0;

        foreach ($globalFeatures as $feature) {
            $model = Feature::firstOrCreate(
                ['name' => $feature['name'], 'scope' => '__laravel_null'],
                ['value' => $feature['value'], 'created_at' => $now, 'updated_at' => $now]
            );

            if ($model->wasRecentlyCreated) {
                $created++;
                $this->command->info(sprintf(
                    'CREATED: %s (scope=%s, value=%s)',
                    $model->name,
                    $model->scope,
                    $model->value
                ));
            } else {
                $existing++;
                $this->command->line(sprintf(
                    'EXISTS : %s (scope=%s, current_value=%s)',
                    $model->name,
                    $model->scope,
                    $model->value
                ));
            }
        }

        $this->command->newLine();
        $this->command->info("Feature flags seeded. Created: {$created}, Existing: {$existing}");
        $this->command->newLine();
    }
}
