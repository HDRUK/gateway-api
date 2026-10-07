<?php

namespace App\Traits;

use App\Events\FederationProcessed;
use App\Http\Traits\MetadataVersioning;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Federation;
use App\Models\FederationJobRun;
use App\Models\Team;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;
use App\Services\Gwdm\GwdmMetadataHandler;
use Carbon\Carbon;
use Config;
use Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MetadataManagementController as MMC;

trait GatewayMetadataIngestionTrait
{
    use MetadataVersioning;

    public function pullCatalogueList(Federation|array $federation, GoogleSecretManagerService $gsms): Collection|array
    {
        if (!is_array($federation)) {
            return $this->getCatalogueFromFederationModel($federation, $gsms);
        }

        return $this->getCatalogueFromFederationArray($federation, $gsms);
    }

    private function getCatalogueFromFederationModel(Federation $federation, GoogleSecretManagerService $gsms): Collection
    {
        $url = $federation->endpoint_baseurl . $federation->endpoint_datasets;
        $this->log('info', "calling REMOTE collection @ {$url}");

        $response = Http::withHeaders(array_merge(
            $this->determineAuthType($federation, $gsms),
            ['Accept' => 'application/json'],
        ))->get($url);
        $this->log('info', "response from REMOTE collection: status={$response->status()}, body=" . json_encode($response->body()));

        if ($response->status() === 200) {
            return collect(json_decode($response->body(), true)['items'])->keyBy('persistentId');
        }

        throw new \RuntimeException(
            "Remote catalogue returned non-200 status {$response->status()} for {$url}: " . $response->body()
        );
    }

    private function getCatalogueFromFederationArray(array $federation, GoogleSecretManagerService $gsms): Collection|array
    {
        $response = Http::withHeaders(
            $this->determineAuthType($federation, $gsms)
        )->get($federation['endpoint_baseurl'] . $federation['endpoint_datasets']);
        if ($response->status() === 200) {
            return collect($response->json()['items'])->keyBy('persistentId');
        }

        return [
            'data' => [
                'errors' => $response->json(),
                'status' => $response->status(),
                'success' => false,
                'title' => 'Test Unsuccessful',
            ],
        ];
    }

    public function getLocalDatasetsForFederatedTeam(GatewayMetadataIngestionService $gmi): Collection
    {
        return collect(Dataset::where([
            'team_id' => $gmi->getTeam(),
        ])->get())->keyBy('pid');
    }

    /**
     * Archives one dataset that's no longer present in the remote catalogue.
     * Returns true if a matching local dataset was found and archived.
     */
    public function archiveFederatedDataset(string $pid, GatewayMetadataIngestionService $gmi): bool
    {
        try {
            $this->log('info', "dataset {$pid} detected LOCALLY, but NOT in REMOTE collection - ARCHIVING");
            $teamId = $gmi->getTeam();
            $ds = Dataset::where([
                'pid' => $pid,
                'team_id' => $teamId,
                'create_origin' => 'GMI',
            ])->first();

            if (!$ds) {
                $this->log('info', "dataset with PID {$pid} was expected locally but not found in DB — skipping archive. This is likely a missmatch of team ids, team id on the incoming dataset: {$teamId}");
                return false;
            }
            $dsId = $ds->id;

            $this->log('info', 'dataset for archiving ' . $dsId);
            $ds->status = Dataset::STATUS_ARCHIVED;
            $ds->save();
            $this->log('info', "dataset {$dsId} archived");

            return true;
        } catch (\Throwable $e) {
            $this->log('error', "encountered internal error while ARCHIVING dataset {$pid}: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches, translates, and stores one dataset that's present in the
     * remote catalogue but not yet known locally. Returns true if it was
     * created.
     */
    public function createFederatedDataset(
        Federation $federation,
        string $pid,
        array $data,
        GoogleSecretManagerService $gms,
        GatewayMetadataIngestionService $gmi,
        string $jobUuid,
        int $attempts,
        GwdmMetadataHandler $handler,
    ): bool {
        try {
            $response = Http::withHeaders($this->determineAuthType($federation, $gms))
                ->get($this->makeDatasetUrl($federation, $data));

            $this->log('info', "attempting to call dataset @ {$pid} from REMOTE collection:
            status={$response->status()}, url={$this->makeDatasetUrl($federation, $data)}");

            if ($response->status() !== 200) {
                $this->recordDatasetFetchFailure($federation, $data, $pid, $response->status(), $gmi, $jobUuid, $attempts);
                return false;
            }

            // pre-check: start
            $team = Team::where('id', $gmi->getTeam())->first();
            $payload = [
                'extra' => [
                    'id' => 'placeholder',
                    'pid' => 'placeholder',
                    'datasetType' => 'Health and disease',
                    'publisherId' => 'placeholder',
                    'publisherName' => $team->name,
                ],
                'metadata' => $response->object(),
            ];
            $traserResponse = MMC::translateDataModelType(
                json_encode($payload),
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version')
            );

            if (!$traserResponse['wasTranslated']) {
                $metadataJson = json_encode($response->object());
                $failure = $this->translationFailureForHistory(
                    $traserResponse,
                    $data,
                    fn (string $schema, string $version) => MMC::validationErrors($metadataJson, $schema, $version)
                );
                $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, $failure, 0, $attempts);

                $this->log('info', "encountered internal error while CREATING dataset {$pid}: cannot not be translated");
                return false;
            }
            // pre-check: end

            $input = [
                'status' => 'ACTIVE',
                'create_origin' => 'GMI',
                'user_id' => Config::get('metadata.system_user_id'),
                'team_id' => $gmi->getTeam(),
                'metadata' => [
                    'metadata' => $response->object(),
                ],
                'pid' => $pid,
            ];

            $result = $gmi->storeMetadata($input, $handler);

            $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, 'CREATED', 1, $attempts);

            $this->log('info', "dataset {$pid} detected in REMOTE collection, but NOT LOCALLY - CREATED");

            return true;
        } catch (\Throwable $e) {
            $this->log('error', "encountered internal error while CREATING dataset {$pid} from remote source: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, "An unexpected error occurred while creating dataset {$pid}. Please contact support and reference job: {$jobUuid}", 0, $attempts);

            return false;
        }
    }

    /**
     * Re-fetches, re-translates, and re-stores one dataset whose remote
     * version differs from what's stored locally. Returns true if it was
     * updated.
     */
    public function updateFederatedDataset(
        Federation $federation,
        string $pid,
        array $data,
        Dataset $local,
        GoogleSecretManagerService $gms,
        GatewayMetadataIngestionService $gmi,
        string $jobUuid,
        int $attempts,
    ): bool {
        try {
            $response = Http::withHeaders($this->determineAuthType($federation, $gms))
                ->get($this->makeDatasetUrl($federation, $data));

            if ($response->status() !== 200) {
                $this->recordDatasetFetchFailure($federation, $data, $pid, $response->status(), $gmi, $jobUuid, $attempts);
                return false;
            }

            $team = Team::where('id', $gmi->getTeam())->first();
            $ds = Dataset::where([
                'pid' => $pid,
                'team_id' => $gmi->getTeam(),
            ])->first();
            $dvModel = DatasetVersion::where('dataset_id', $local->id)->orderBy('id', 'desc')->first();

            if (!$dvModel) {
                $this->log('warning', "dataset {$pid} has no version record locally - skipping update");
                return false;
            }

            if ($ds->status === Dataset::STATUS_ARCHIVED) {
                $conflictingActiveId = Dataset::where([
                    'pid' => $pid,
                    'team_id' => $gmi->getTeam(),
                    'status' => Dataset::STATUS_ACTIVE,
                ])
                    ->where('id', '!=', $ds->id)
                    ->value('id');

                if ($conflictingActiveId) {
                    $this->log('warning', "dataset {$pid} reappeared in REMOTE collection but ACTIVE dataset id={$conflictingActiveId} with the same PID already exists locally in place of ARCHIVED dataset id={$ds->id} - skipping re-publish to avoid duplicate PID");
                    return false;
                }

                $ds->status = Dataset::STATUS_ACTIVE;
                $ds->save();
                $this->log('info', "dataset {$pid} REPUBLISHED (was ARCHIVED, reappeared in REMOTE collection)");
            }

            $dv = $dvModel->toArray();
            $localVersion = $dv['metadata']['metadata']['required']['version'] ?? null;

            if (!$localVersion) {
                $this->log('warning', "dataset {$pid} has no parseable version in local metadata - skipping update");
                return false;
            }

            $payload = [
                'extra' => [
                    'id' => $ds->id,
                    'pid' => $ds->pid,
                    'datasetType' => 'Health and disease',
                    'publisherId' => $team->pid,
                    'publisherName' => $team->name,
                ],
                'metadata' => $response->object(),
            ];

            $this->log('info', "version compare of REMOTE v{$data['version']} and LOCAL v{$localVersion}");

            if (!version_compare($data['version'], $localVersion, '<>')) {
                $this->log('info', "dataset {$pid} nothing to update - IGNORING");
                return false;
            }

            $this->log('info', "dataset {$pid} found version difference in REMOTE metadata of v{$data['version']} vs local {$localVersion} - UPDATING LOCAL");
            $traserResponse = MMC::translateDataModelType(
                json_encode($payload),
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version')
            );

            $wasUpdated = false;

            if ($traserResponse['wasTranslated']) {
                $ds->update([
                    'updated' => Carbon::now(),
                ]);

                $versionNumber = $ds->lastMetadataVersionNumber()->version;
                $dsId = $this->updateMetadataVersion(
                    $ds,
                    $traserResponse['metadata'],
                    $data,
                );

                $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, 'UPDATED', 1, $attempts);

                $wasUpdated = true;
            } else {
                $this->log('info', "dataset {$pid} FAILED traser");

                $metadataJson = json_encode($response->object());
                $failure = $this->translationFailureForHistory(
                    $traserResponse,
                    $data,
                    fn (string $schema, string $version) => MMC::validationErrors($metadataJson, $schema, $version)
                );
                $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, $failure, 0, $attempts);
            }

            $this->log('info', "dataset {$pid} detected as CHANGED in REMOTE collection - UPDATED");

            return $wasUpdated;
        } catch (\Throwable $e) {
            $this->log('error', "encountered internal error while UPDATING dataset {$pid} from remote source: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, "An unexpected error occurred while updating dataset {$pid}. Please contact support and reference job: {$jobUuid}", 0, $attempts);

            return false;
        }
    }

    public function determineAuthType(Federation|array $federation, GoogleSecretManagerService $gsms, bool $testMode = false): array
    {
        if (!is_array($federation) && !$testMode) {
            switch ($federation->auth_type) {
                case 'BEARER':
                    $key = $gsms->getSecret($federation->auth_secret_key_location);
                    return [
                        'Authorization' => 'Bearer ' . json_decode($key, true)['bearer_token'],
                    ];
                case 'API_KEY':
                    $key = $gsms->getSecret($federation->auth_secret_key_location);
                    return [
                        'apikey' => json_decode($key, true)['api_key'],
                    ];
                case 'NO_AUTH':
                    // Nothing to do
                    return [];
                default:
                    Log::error('unknown auth_type ' . $federation->auth_type . ' - aborting');
                    return [];
            }
        } else {
            switch ($federation['auth_type']) {
                case 'BEARER':
                    return [
                        'Authorization' => 'Bearer ' . $federation['auth_secret_key'],
                    ];
                case 'API_KEY':
                    return [
                        'apikey' => $federation['auth_secret_key'],
                    ];
                case 'NO_AUTH':
                    return [];
                default:
                    Log::error('unknown auth_type ' . $federation['auth_type'] . ' - aboring');
                    return [];
            }
        }
    }

    private function recordDatasetFetchFailure(
        Federation $federation,
        array $data,
        string $pid,
        int $status,
        GatewayMetadataIngestionService $gmi,
        string $jobUuid,
        int $attempts,
    ): void {
        $url = $this->makeDatasetUrl($federation, $data);
        $this->log('warning', "dataset {$pid} fetch from REMOTE returned status={$status}, url={$url}");
        $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, "Remote dataset endpoint returned status {$status} for {$url}", 0, $attempts);
    }

    public function makeDatasetUrl(Federation $federation, array $data): string
    {
        return $federation->endpoint_baseurl .
            str_replace('{id}', $data['persistentId'], $federation->endpoint_dataset);
    }

    public function log(string $level, string $message): void
    {
        Log::{$level}($message);
    }

    /**
     * What to record when traser can't translate a dataset: only the errors that blocked it.
     *
     * @param array{traser_message?: mixed} $traserResponse MMC::translateDataModelType() result
     * @param array<string, mixed> $catalogueItem the dataset's entry in the remote catalogue
     * @param \Closure(string, string): array $validateAgainst traser's errors for one schema (name, version); only called when a schema is declared
     */
    public function translationFailureForHistory(array $traserResponse, array $catalogueItem, \Closure $validateAgainst): array|string
    {
        $traser = $traserResponse['traser_message'] ?? null;
        $message = is_array($traser) ? ($traser['message'] ?? null) : $traser;
        $details = is_array($traser) ? ($traser['details'] ?? null) : null;

        // Output validation failed: the GWDM errors are what blocked it.
        if (is_array($details) && array_is_list($details) && $details !== []) {
            return [[
                'name' => Config::get('metadata.GWDM.name'),
                'version' => Config::get('metadata.GWDM.version'),
                'errors' => $details,
            ]];
        }

        // No input schema matched: report only the schema the provider declared.
        if (is_array($details) && isset($details['available_schemas'])) {
            $declared = declaredMetadataSchema($catalogueItem);
            $errors = $declared ? $validateAgainst($declared['name'], $declared['version']) : [];

            return $errors !== []
                ? [['name' => $declared['name'], 'version' => $declared['version'], 'errors' => $errors]]
                : "Doesn't match any supported metadata schema";
        }

        return is_string($message) && $message !== '' ? $message : 'An error occurred while processing this dataset.';
    }

    public function sendToHistory(int $teamId, int $federationId, string $pid, string $jobUuid, array|string $message, int $status, int $attempts): void
    {
        FederationJobRun::create(
            [
                'team_id' => $teamId,
                'federation_id' => $federationId,
                'pid' => $pid,
                'job_uuid' => $jobUuid,
                'status' => $status,
                'details' => [
                    'message' => $message,
                ],
                'job_attempts' => $attempts,
            ]
        );
    }

    /**
     * Concludes one federation execution: checks the per-dataset history
     * recorded under $jobUuid (plus $batchHadFailures, for a failure that
     * never reached sendToHistory at all — e.g. a chunk job hard-failing
     * before its first item) and either records a soft failure on the
     * federation directly, or dispatches FederationProcessed so
     * ProcessFederationSuccess can clear the federation's error state,
     * clear is_running, and send the success notification.
     */
    public function finaliseFederationRun(int $federationId, string $jobUuid, bool $batchHadFailures = false, bool $batchCancelled = false): void
    {
        if ($batchCancelled) {
            $finalised = Federation::where('id', $federationId)
                ->where('is_running', true)
                ->update(['is_running' => false]);

            $this->log('info', $finalised === 0
                ? "federation {$federationId} run {$jobUuid} already finalised - skipping"
                : "federation {$federationId} run {$jobUuid} stopped after its batch was cancelled");

            return;
        }

        $hadFailures = $batchHadFailures
            || FederationJobRun::latestPerPidForExecution($federationId, $jobUuid)
                ->contains(fn ($run) => $run->status === 0);

        $finalised = Federation::where('id', $federationId)
            ->where('is_running', true)
            ->update($hadFailures
                ? [
                    'is_running' => false,
                    'error' => true,
                    'error_text' => "Run completed with errors for one or more datasets. Please check the run history for job: {$jobUuid}",
                ]
                : ['is_running' => false]);

        if ($finalised === 0) {
            $this->log('info', "federation {$federationId} run {$jobUuid} already finalised - skipping");
            return;
        }

        if ($hadFailures) {
            $this->log('warning', "federation {$federationId} completed with per-item failures - see federation_job_runs for details");
            return;
        }

        FederationProcessed::dispatch(Federation::find($federationId), $jobUuid);
    }

    public function reconcileStuckFederationRuns(): int
    {
        $reconciled = 0;

        $running = Federation::where('is_running', true)
            ->whereNotNull('current_batch_id')
            ->get(['id', 'current_batch_id']);

        foreach ($running as $federation) {
            $batch = Bus::findBatch($federation->current_batch_id);

            if (is_null($batch) || batchHasJobsLeftToRun($batch)) {
                continue;
            }

            $jobUuid = federationJobUuidFromBatchName($federation->id, $batch->name);

            if (is_null($jobUuid)) {
                $this->log('warning', "federation {$federation->id} batch {$batch->id} has an unexpected name '{$batch->name}' - not reconciling");
                continue;
            }

            $wasReconciled = DB::transaction(function () use ($federation, $batch, $jobUuid) {
                $stillStuck = Federation::where('id', $federation->id)
                    ->where('is_running', true)
                    ->where('current_batch_id', $batch->id)
                    ->lockForUpdate()
                    ->exists();

                if (!$stillStuck) {
                    return false;
                }

                $this->log('warning', "federation {$federation->id} still running with no jobs left in batch {$batch->id} - finalising run {$jobUuid}");
                $this->finaliseFederationRun($federation->id, $jobUuid, $batch->hasFailures(), $batch->cancelled());

                return true;
            });

            if ($wasReconciled) {
                $reconciled++;
            }
        }

        return $reconciled;
    }

}
