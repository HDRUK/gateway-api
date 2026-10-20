<?php

use App\DeploymentSteps\DeploymentStep;

/**
 * Seed the sample PRUK dataset and CROCOD trial case study (GWDM 2.3) from
 * tests/Unit/test_files/{pruk,crocod}_dummy_data, for testing GWDM 2.3 storage
 * and rendering end to end.
 *
 * Only runs in local/dev. PrukCrocodSampleSeeder is idempotent, so re-running
 * refreshes the samples rather than duplicating them.
 */
return new class () extends DeploymentStep {
    public function handle(): void
    {
        if (!app()->environment(['local', 'dev'])) {
            $this->warn('Skipping PRUK/CROCOD samples: only runs in local/dev environments.');

            return;
        }

        $this->call('db:seed', ['--class' => 'PrukCrocodSampleSeeder', '--force' => true]);
    }
};
