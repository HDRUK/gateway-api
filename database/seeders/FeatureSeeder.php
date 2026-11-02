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
        'V2_SearchAggregation' => false,
        'V3_CustodianDashboard' => false,
        'TypesenseSearch' => false,
        // Renders /dataset/{id} from native GWDM (gateway-web). Off = the
        // existing HDRUK-translated page.
        'GwdmDatasetPage' => false,
        // Generates the metadata onboarding form from the selected input
        // schema (gateway-web) and validates GWDM on activation. Off = the
        // existing form hydration flow.
        'SchemaOnboardingForm' => false,
        // Let the HDRUK context read and render each partner's datasets
        // (PartnerContext::CROSS_CONTEXT_FLAGS). Off = HDRUK datasets only.
        'RenderSchemaCRUK' => false,
        'RenderSchemaPRUK' => false,
        'RenderSchemaCROCOD' => false,
        'SafePeopleRegistrySSOEnabled' => false,
        'LoginDisabled' => false,
        'LoginGoogleEnabled' => true,
        'LoginAzureEnabled' => true,
        'LoginLinkedInEnabled' => true,
        'LoginOpenAthensEnabled' => true,
        'LoginRegistryEnabled' => true,
    ];

    public function run(): void
    {
        $this->requirePennantDatabaseStore();

        $created = 0;
        $existing = 0;

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
