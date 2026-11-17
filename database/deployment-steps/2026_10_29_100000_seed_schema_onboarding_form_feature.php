<?php

use App\DeploymentSteps\DeploymentStep;

/**
 * Seed the `SchemaOnboardingForm` flag added in GAT-9744, so it is present in
 * the `features` table and therefore toggleable from the search admin screen
 * without a deploy.
 *
 * It is seeded off: with no stored value Pennant resolves it false anyway, and
 * false is the existing onboarding form with unvalidated activation, so an
 * environment that misses this step behaves as it does today. Seeding only
 * makes the switch visible and operable.
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
