<?php

namespace App\Console\Commands;

use App\Context\GwdmVersionContext;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use Config;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use MetadataManagementController as MMC;

/**
 * Replays the GWDM reconstruct/validate/translate steps that
 * DatasetService::prepareForShow() runs for GET /api/v2/datasets/{id}
 * (Api\V2\DatasetController@showActive), calling the DatasetService /
 * GwdmHandlerFactory / MMC layers directly instead of dispatching through
 * the route/controller/resource stack.
 *
 * Unlike the real read path (which only validates when a TRASER translation
 * is requested), this command always runs + logs the TRASER validation step
 * so build/validate failures are visible even without a --schema-model
 * target, plus dumps the exact payload sent to TRASER when a step fails.
 */
class ShowDatasetV2 extends Command
{
    protected $signature = 'app:show-dataset-v2
                            {id : The dataset id (integer)}
                            {--partner= : partner_context filter passed to DatasetService::findActive}
                            {--schema-model= : output schema_model to translate to via TRASER}
                            {--schema-version= : output schema_version to translate to via TRASER}
                            {--output= : Write the resulting JSON to this file instead of stdout}';

    protected $description = 'Rebuild (and optionally translate) a dataset\'s GWDM metadata directly via DatasetService/GwdmHandler/TRASER, logging each stage for debugging translation failures';

    public function handle(
        DatasetService $datasetService,
        GwdmHandlerFactory $handlerFactory,
        GwdmVersionContext $gwdmVersionContext,
    ): int {
        $id = (int) $this->argument('id');
        $partner = $this->option('partner');
        $schemaModel = $this->option('schema-model');
        $schemaVersion = $this->option('schema-version');

        if (($schemaModel && ! $schemaVersion) || ($schemaVersion && ! $schemaModel)) {
            $this->error('--schema-model and --schema-version must be provided together.');

            return self::FAILURE;
        }

        $dataset = $datasetService->findActive($id, $partner);
        if (! $dataset) {
            $this->error("No ACTIVE dataset found for id {$id}".($partner ? " (partner_context={$partner})" : ''));

            return self::FAILURE;
        }

        $this->info("Found dataset {$dataset->id} (pid={$dataset->pid}), status={$dataset->status}");

        $latestVersion = $dataset->versions()
            ->select(['id', 'version', 'gwdm_version'])
            ->orderBy('version', 'desc')
            ->first();

        if (! $latestVersion) {
            $this->error("Dataset {$id} has no versions.");

            return self::FAILURE;
        }

        $storedGwdmVersion = $latestVersion->gwdm_version ?? $gwdmVersionContext->targetVersion();
        $this->line("Latest version: v{$latestVersion->version} (id={$latestVersion->id}, gwdm_version={$storedGwdmVersion})");

        $handler = $handlerFactory->resolve($storedGwdmVersion);
        $this->line('Resolved handler: '.get_class($handler));

        // --- Build: snapshot + delta replay (2.x) / SQL table reconstruction (3.0) ---
        // Never validate here so we always get the envelope back for inspection,
        // even when the subsequent TRASER validation step below fails.
        try {
            $envelope = $datasetService->getReconstructedMetadataEnvelope(
                $dataset->id,
                $latestVersion->version,
                validate: false,
            );
        } catch (\Throwable $e) {
            $this->error('Failed to BUILD reconstructed GWDM: '.$e->getMessage());
            $this->line($e->getFile().':'.$e->getLine());

            Log::warning('app:show-dataset-v2 GWDM build failed', [
                'dataset_id' => $id,
                'version' => $latestVersion->version,
                'gwdm_version' => $storedGwdmVersion,
                'handler' => get_class($handler),
                'exception' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $this->info('Built reconstructed GWDM envelope OK (gwdmVersion='.$envelope['gwdmVersion'].')');

        // --- Validate: same call prepareForShow makes, but run unconditionally ---
        // here (not gated on --schema-model) so build/validate problems surface
        // even when you're not chasing a specific translation target.
        $metadataJson = json_encode(['metadata' => $envelope['metadata']]);
        $isValid = MMC::validateDataModelType($metadataJson, Config::get('metadata.GWDM.name'), $envelope['gwdmVersion']);

        if (! $isValid) {
            $this->error("Reconstructed GWDM failed TRASER schema validation against {$envelope['gwdmVersion']}.");
            $this->line('See the "GWDM validation rejected by TRASER" warning in the log for the TRASER response body.');

            Log::warning('app:show-dataset-v2 GWDM validation failed', [
                'dataset_id' => $id,
                'version' => $latestVersion->version,
                'gwdm_version' => $envelope['gwdmVersion'],
                'handler' => get_class($handler),
                'payload' => $metadataJson,
            ]);

            if ($dumpPath = $this->option('output')) {
                file_put_contents($dumpPath, $metadataJson.PHP_EOL);
                $this->line("Wrote the invalid GWDM payload to {$dumpPath} for inspection.");
            }

            if (! $schemaModel) {
                return self::FAILURE;
            }
        } else {
            $this->info('TRASER schema validation passed.');
        }

        if (! $schemaModel) {
            return $this->emit($envelope);
        }

        // --- Translate: same MMC call prepareForShow makes when schema_model/schema_version given ---
        $this->line("Translating {$envelope['gwdmVersion']} -> {$schemaModel}:{$schemaVersion} via TRASER...");

        $requestJson = json_encode($envelope);

        $translated = MMC::translateDataModelType(
            $requestJson,
            $schemaModel,
            $schemaVersion,
            Config::get('metadata.GWDM.name'),
            $envelope['gwdmVersion'],
        );

        if (! $translated['wasTranslated']) {
            $traserError = is_array($translated['traser_message'])
                ? json_encode($translated['traser_message'], JSON_PRETTY_PRINT)
                : ($translated['traser_message'] ?? 'unknown error');

            $this->error("TRASER translation failed (HTTP {$translated['statusCode']}):");
            $this->line($traserError);

            Log::warning('app:show-dataset-v2 TRASER translation failed', [
                'dataset_id' => $id,
                'version' => $latestVersion->version,
                'gwdm_version' => $envelope['gwdmVersion'],
                'target_schema' => "{$schemaModel}:{$schemaVersion}",
                'status' => $translated['statusCode'],
                'traser_message' => $translated['traser_message'],
                'payload' => $requestJson,
            ]);

            if ($dumpPath = $this->option('output')) {
                file_put_contents($dumpPath, $requestJson.PHP_EOL);
                $this->line("Wrote the untranslated GWDM payload sent to TRASER to {$dumpPath} for inspection.");
            }

            return self::FAILURE;
        }

        $this->info('TRASER translation succeeded.');

        return $this->emit(['metadata' => $translated['metadata']]);
    }

    private function emit(array $payload): int
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($outputPath = $this->option('output')) {
            file_put_contents($outputPath, $json.PHP_EOL);
            $this->info("Written to {$outputPath}");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }
}
