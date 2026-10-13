<?php

use App\DeploymentSteps\DeploymentStep;

/**
 * Seed the per-provider sign-in feature flags added in GAT-9689, so they
 * are present in the `features` table and therefore toggleable from the
 * search admin screen.
 *
 * FeatureSeeder skips any flag already stored, so this neither resets a
 * flag an operator has since turned off nor conflicts with the defaults
 * AppFeatureServiceProvider defines.
 */
return new class () extends DeploymentStep {
    public function handle(): void
    {
        $this->call('db:seed', ['--class' => 'FeatureSeeder', '--force' => true]);
    }
};
