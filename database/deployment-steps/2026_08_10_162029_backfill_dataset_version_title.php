<?php

use App\DeploymentSteps\DeploymentStep;
use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use Illuminate\Support\Facades\Config;

/**
 * Back-fill dataset_versions.title/short_title for rows left NULL by write
 * paths that never set them (see MetadataOnboard::metadataOnboard() fix,
 * GAT-9343).
 */
return new class () extends DeploymentStep {
    public function handle(): void
    {
        $datasetService = app(DatasetService::class);
        $handlerFactory = app(GwdmHandlerFactory::class);

        $fixed = 0;
        $failed = 0;

        DatasetVersion::whereNull('title')
            ->whereNull('short_title')
            ->chunkById(200, function ($versions) use ($datasetService, $handlerFactory, &$fixed, &$failed) {
                foreach ($versions as $version) {
                    try {
                        $envelope = $datasetService->getReconstructedMetadataEnvelope(
                            $version->dataset_id,
                            $version->version,
                            validate: false,
                            prefetched: $version,
                            applySupplementary: false,
                        );

                        $gwdmVersion = $envelope['gwdmVersion'] ?? Config::get('metadata.GWDM.version');
                        $handler = $handlerFactory->resolve($gwdmVersion);
                        [$title, $shortTitle] = $handler->extractTitleFields($envelope['metadata']);

                        if ($title === null) {
                            $failed++;
                            $this->warn("dataset_version {$version->id}: reconstructed metadata has no title, skipping.");
                            continue;
                        }

                        $version->forceFill([
                            'title' => $title,
                            'short_title' => $shortTitle,
                        ])->saveQuietly();

                        $fixed++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->warn("dataset_version {$version->id}: could not be reconstructed — {$e->getMessage()}");
                    }
                }
            });

        $this->info("Back-filled title/short_title on {$fixed} dataset_version row(s); {$failed} could not be reconstructed and remain NULL.");
    }
};
