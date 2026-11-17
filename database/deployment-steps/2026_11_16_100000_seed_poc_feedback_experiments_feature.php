<?php

use App\DeploymentSteps\DeploymentStep;

/**
 * Seed the `POC_FeedbackExperiments` flag added in GAT-9857, so it is present in the
 * `features` table and therefore toggleable from the search admin screen
 * without a deploy.
 *
 * It is seeded off: with no stored value Pennant resolves it false anyway, and
 * false means no feedback prompts are served, so an environment that misses
 * this step shows none. Seeding only makes the switch visible and operable.
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
