<?php

use App\DeploymentSteps\DeploymentStep;

/**
 * Seed the `SchemaOnboardingFormSelector` flag added in GAT-9853, so it is
 * present in the `features` table and therefore toggleable from the search
 * admin screen without a deploy.
 *
 * It is seeded off: with no stored value Pennant resolves it false anyway, and
 * false is the onboarding form fixed to its default schema, so an environment
 * that misses this step behaves as it does today.
 *
 * FeatureSeeder skips any flag already stored, so this neither resets a flag an
 * operator has since turned on nor touches the other defaults.
 */
return new class () extends DeploymentStep {
    public function handle(): void
    {
        $this->call('db:seed', ['--class' => 'FeatureSeeder', '--force' => true]);
    }
};
