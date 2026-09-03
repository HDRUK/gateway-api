<?php

use App\DeploymentSteps\DeploymentStep;
use App\Models\Dur;
use App\Models\DurOutput;

/**
 * One-time backfill: turn every existing dur.non_gateway_outputs URL into a
 * dur_outputs row.
 */
return new class () extends DeploymentStep {
    public function handle(): void
    {
        $created = 0;
        $skippedBlank = 0;

        Dur::whereNotNull('non_gateway_outputs')
            ->chunkById(200, function ($durs) use (&$created, &$skippedBlank) {
                foreach ($durs as $dur) {
                    foreach ($dur->non_gateway_outputs ?? [] as $url) {
                        $trimmed = is_string($url) ? trim($url) : '';

                        if ($trimmed === '') {
                            $skippedBlank++;
                            continue;
                        }

                        DurOutput::create([
                            'dur_id' => $dur->id,
                            'url' => $trimmed,
                        ]);

                        $created++;
                    }
                }
            });

        $this->info("Created {$created} dur_outputs row(s) from existing non_gateway_outputs; skipped {$skippedBlank} blank entrie(s).");
    }
};
