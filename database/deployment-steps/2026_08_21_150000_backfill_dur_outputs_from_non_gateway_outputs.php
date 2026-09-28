<?php

use App\DeploymentSteps\DeploymentStep;
use App\Models\Dur;
use App\Models\DurOutput;

/**
 * One-time backfill: turn every existing dur.non_gateway_outputs entry into
 * a dur_outputs row.
 */
return new class () extends DeploymentStep {
    public function handle(): void
    {
        DurOutput::truncate();

        $createdAsUrl = 0;
        $createdAsDetail = 0;
        $skippedBlank = 0;

        Dur::whereNotNull('non_gateway_outputs')
            ->chunkById(200, function ($durs) use (&$createdAsUrl, &$createdAsDetail, &$skippedBlank) {
                foreach ($durs as $dur) {
                    foreach ($dur->non_gateway_outputs ?? [] as $value) {
                        $trimmed = is_string($value) ? trim($value) : '';

                        if ($trimmed === '') {
                            $skippedBlank++;
                            continue;
                        }

                        $isUrl = filter_var($trimmed, FILTER_VALIDATE_URL) !== false;

                        DurOutput::create([
                            'dur_id' => $dur->id,
                            'url' => $isUrl ? $trimmed : null,
                            'detail' => $isUrl ? null : $trimmed,
                        ]);

                        $isUrl ? $createdAsUrl++ : $createdAsDetail++;
                    }
                }
            });

        $this->info(
            "Created {$createdAsUrl} dur_outputs row(s) as a url and {$createdAsDetail} as free-text detail "
            . "from existing non_gateway_outputs; skipped {$skippedBlank} blank entrie(s)."
        );
    }
};
