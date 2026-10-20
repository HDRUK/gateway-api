<?php

use App\DeploymentSteps\DeploymentStep;

/**
 * Seed the `RenderSchemaCRUK`, `RenderSchemaPRUK` and `RenderSchemaCROCOD`
 * flags, which replace the HDRUK_CROSS_CONTEXT_READ env var, so they are
 * present in the `features` table and therefore toggleable from the search
 * admin screen without a deploy.
 *
 * They are seeded off: with no stored value Pennant resolves them false
 * anyway, and false means the HDRUK context reads only HDRUK datasets, so an
 * environment that misses this step fails closed. Seeding only makes the
 * switches visible and operable.
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
