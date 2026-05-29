<?php

namespace App\Traits;

use App\Http\Traits\MetadataVersioning;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Federation;
use App\Models\FederationJobRun;
use App\Models\Team;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;
use Carbon\Carbon;
use Config;
use Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use MetadataManagementController as MMC;

trait GatewayMetadataIngestionTrait
{
    use MetadataVersioning;

    public function pullCatalogueList(Federation|array $federation, GoogleSecretManagerService $gsms): Collection
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

        $response = Http::get(
            $url,
            [
                $this->determineAuthType($federation, $gsms),
                'Accept' => 'application/json',
            ]
        );
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
        $response = Http::get(
            $federation['endpoint_baseurl'] . $federation['endpoint_datasets'],
            $this->determineAuthType($federation, $gsms)
        );
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

    public function archiveLocalDatasetsNotInRemoteCatalogue(
        Collection $localItems,
        Collection $remoteItems,
        GatewayMetadataIngestionService $gmi,
        Federation $federation,
        string $jobUuid,
        int $attempts
    ): int {
        $this->log('info', 'testing REMOTE collection for LOCAL archive');

        $archivedCount = 0;

        $toArchive = $localItems->keys()->diff($remoteItems->keys());

        foreach ($toArchive as $pid) {
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
                    continue;
                }
                $dsId = $ds->id;

                $this->log('info', 'dataset for archiving ' . $dsId);
                $ds->status = Dataset::STATUS_ARCHIVED;
                $ds->save();
                $this->log('info', "dataset {$dsId} archived");

                unset($ds);
                $archivedCount++;
            } catch (\Throwable $e) {
                $this->log('error', "encountered internal error while ARCHIVING dataset {$pid}: " . $e->getMessage());
            }
        }

        return $archivedCount;
    }

    public function createLocalDatasetsMissingFromRemoteCatalogue(
        Collection $localItems,
        Collection $remoteItems,
        Federation $federation,
        GoogleSecretManagerService $gms,
        GatewayMetadataIngestionService $gmi,
        ?string $jobUuid,
        int $attempts
    ): int {
        $createdCount = 0;
        $toCreate = $remoteItems->keys()->diff($localItems->keys());
        foreach ($toCreate as $pid) {
            $existDataset = Dataset::where([
                    'pid' => $pid,
                    'team_id' => $gmi->getTeam(),
                ])
                ->exists();
            if ($existDataset) {
                $this->log('info', "attempted to re-create a dataset that already exists @ {$pid}");
                continue;
            }

            try {
                $data = $remoteItems[$pid];
                $response = Http::get($this->makeDatasetUrl($federation, $data), $this->determineAuthType($federation, $gms));

                $this->log('info', "attempting to call dataset @ {$pid} from REMOTE collection:
                status={$response->status()}, url={$this->makeDatasetUrl($federation, $data)}");

                if ($response->status() === 200) {
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
                        $findTraserResponse = MMC::findDataModel(json_encode($response->object()));
                        $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, $findTraserResponse, 0, $attempts);

                        $this->log('info', "encountered internal error while CREATING dataset {$pid}: cannot not be translated");
                        continue;
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

                    $result = $gmi->storeMetadata($input);

                    $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, 'CREATED', 1, $attempts);

                    $createdCount++;
                    $this->log('info', "dataset {$pid} detected in REMOTE collection, but NOT LOCALLY - CREATED");
                }
            } catch (\Throwable $e) {
                $this->log('error', "encountered internal error while CREATING dataset {$pid} from remote source: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, "encountered internal error while CREATING dataset {$pid} from remote source: " . $e->getMessage(), 0, $attempts);
            }

        }

        return $createdCount;
    }

    public function updateLocalDatasetsChangedInRemoteCatalogue(
        Collection $localItems,
        Collection $remoteItems,
        Federation $federation,
        GoogleSecretManagerService $gms,
        GatewayMetadataIngestionService $gmi,
        string $jobUuid,
        int $attempts
    ): int {
        $updatedCount = 0;
        foreach ($remoteItems as $pid => $data) {
            if ($localItems->has($pid)) {
                try {
                    $local = $localItems[$pid];
                    $response = Http::get($this->makeDatasetUrl($federation, $data), $this->determineAuthType($federation, $gms));
                    if ($response->status() === 200) {
                        $team = Team::where('id', $gmi->getTeam())->first();
                        $ds = Dataset::where([
                            'pid' => $pid,
                            'team_id' => $gmi->getTeam(),
                        ])->first();
                        $dvModel = DatasetVersion::where('dataset_id', $local->id)->orderBy('id', 'desc')->first();

                        if (!$dvModel) {
                            $this->log('warning', "dataset {$pid} has no version record locally - skipping update");
                            continue;
                        }

                        $dv = $dvModel->toArray();
                        $localVersion = $dv['metadata']['metadata']['required']['version'] ?? null;

                        if (!$localVersion) {
                            $this->log('warning', "dataset {$pid} has no parseable version in local metadata - skipping update");
                            continue;
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

                        if (version_compare($data['version'], $localVersion, '<>')) {
                            $this->log('info', "dataset {$pid} found version difference in REMOTE metadata of v{$data['version']} vs local {$localVersion} - UPDATING LOCAL");
                            $traserResponse = MMC::translateDataModelType(
                                json_encode($payload),
                                Config::get('metadata.GWDM.name'),
                                Config::get('metadata.GWDM.version')
                            );

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

                                $updatedCount++;
                            } else {
                                $this->log('info', "dataset {$pid} FAILED traser");

                                $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, $traserResponse, 0, $attempts);
                            }

                            $this->log('info', "dataset {$pid} detected as CHANGED in REMOTE collection - UPDATED");
                        } else {
                            $this->log('info', "dataset {$pid} nothing to update - IGNORING");
                        }
                    }
                } catch (\Throwable $e) {
                    $this->log('error', "encountered internal error while UPDATING dataset {$pid} from remote source: " . $e->getMessage() . "\n" . $e->getTraceAsString());

                    $this->sendToHistory($gmi->getTeam(), $federation->id, $pid, $jobUuid, "encountered internal error while UPDATING dataset {$pid} from remote source: " . $e->getMessage(), 0, $attempts);
                }
            }
        }

        return $updatedCount;
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
                        'apikey' => $key,
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

    public function makeDatasetUrl(Federation $federation, array $data): string
    {
        return $federation->endpoint_baseurl .
            str_replace('{id}', $data['persistentId'], $federation->endpoint_dataset);
    }

    public function log(string $level, string $message): void
    {
        Log::{$level}($message);
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

}
