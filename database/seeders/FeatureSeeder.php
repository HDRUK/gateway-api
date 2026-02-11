<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Laravel\Pennant\Feature;

class FeatureSeeder extends Seeder
{
    private static array $defaults = [
        'SDEConciergeServiceEnquiry' => true,
        'Aliases' => true,
        'NhsSdeApplicationsEnabled' => false,
        'Widgets' => false,
        'RQuest' => true,
        'CohortDiscoveryService' => false,
    ];

    public function run(): void
    {
        $this->requirePennantDatabaseStore();

        $created = 0;
        $existing = 0;

        //$store = Feature::store();
        $storedNames = \DB::table('features')
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        foreach (self::$defaults as $name => $defaultValue) {
            if (in_array($name, $storedNames, true)) {
                $existing++;

                continue;
            }
            if ($defaultValue) {
                Feature::activate($name);
            } else {
                Feature::deactivate($name);
            }
            $this->command->info(
                'CREATED: '.$name.' = '.var_export($defaultValue, true)
            );
            $created++;
        }

        Feature::flushCache();

        $this->command->newLine();
        $this->command->info("Feature flags seeded. Created: {$created}, Existing: {$existing}");
        $this->command->newLine();
    }

    private function requirePennantDatabaseStore(): void
    {
        $storeName = config('pennant.default', 'array');
        $driver = config("pennant.stores.$storeName.driver", 'array');

        if ($driver !== 'database') {
            throw new Exception('FeatureSeeder requires PENNANT_STORE=database.');
        }
    }
}
