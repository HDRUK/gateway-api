<?php

namespace App\Services;

use App\Exceptions\FederationSecretException;
use App\Http\Traits\RequestTransformation;
use App\Jobs\ProcessFederation;
use App\Models\Federation;
use App\Models\FederationHasNotification;
use App\Models\FederationJobRun;
use App\Models\Notification;
use App\Models\Role;
use App\Models\TeamHasFederation;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use App\Models\User;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class FederationService
{
    use RequestTransformation;

    public function listForTeam(int $teamId, int $perPage): LengthAwarePaginator
    {
        $federations = Federation::whereHas('team', function ($query) use ($teamId) {
            $query->where('id', $teamId);
        })->with(['team', 'notifications.userNotification'])->paginate($perPage, ['*'], 'page');

        $federationIds = $federations->getCollection()->map(fn ($federation) => $federation->id)->all();
        $lastRunTimes = $federationIds === [] ? collect() : FederationJobRun::latestRunTimesForFederationIds($federationIds);

        $federations->getCollection()->transform(function ($federation) use ($lastRunTimes) {
            $federation->setAttribute('auth_secret_key', $this->decryptAuthSecretKey(
                $federation->auth_secret_key_location,
                $federation->auth_type
            ));
            $federation->setAttribute('last_run_at', $lastRunTimes->get($federation->id));
            return $federation;
        });

        return $federations;
    }

    public function getForTeam(int $teamId, int $federationId): array
    {
        $federation = Federation::whereHas('team', function ($query) use ($teamId) {
            $query->where('id', $teamId);
        })->where('id', $federationId)->with(['team', 'notifications.userNotification'])->first();

        if (is_null($federation)) {
            throw new Exception('Federation not found!');
        }

        $federation = $federation->toArray();
        $federation['auth_secret_key'] = $this->decryptAuthSecretKey(
            $federation['auth_secret_key_location'] ?? null,
            $federation['auth_type'] ?? null
        );

        return $federation;
    }

    public function create(int $teamId, array $input): Federation
    {
        $payload = [
            'federation_type' => $input['federation_type'],
            'auth_type' => $input['auth_type'],
            'auth_secret_key_location' => null,
            'endpoint_baseurl' => $input['endpoint_baseurl'],
            'endpoint_datasets' => $input['endpoint_datasets'],
            'endpoint_dataset' => $input['endpoint_dataset'],
            'run_time_hour' => $input['run_time_hour'],
            'run_time_minute' => $input['run_time_minute'],
            'enabled' => $input['enabled'],
            'enabled_at' => $input['enabled'] ? now() : null,
            'tested' => array_key_exists('tested', $input) ? $input['tested'] : 0,
        ];

        $federation = Federation::create($payload);

        $secretsPayload = $this->getSecretsPayload($input);

        if ($secretsPayload) {
            $authSecretKeyLocation = config('gateway.google_secrets_gmi_prepend_name') . $federation->id;

            try {
                app(GoogleSecretManagerService::class)->createSecret($authSecretKeyLocation, json_encode($secretsPayload));
            } catch (Exception $e) {
                Federation::where('id', $federation->id)->delete();
                throw new FederationSecretException('failed to save secrets for this federation', $e->getMessage());
            }

            Federation::where('id', $federation->id)->update(['auth_secret_key_location' => $authSecretKeyLocation]);
        }

        TeamHasFederation::create([
            'federation_id' => $federation->id,
            'team_id' => $teamId,
        ]);

        $this->replaceNotifications($federation->id, $input['notifications']);

        $this->sendEmail($federation->id, 'CREATE');

        return $federation;
    }

    public function update(int $teamId, int $federationId, array $input): ?Federation
    {
        $updateArray = [
            'federation_type' => $input['federation_type'],
            'auth_type' => $input['auth_type'],
            'endpoint_baseurl' => $input['endpoint_baseurl'],
            'endpoint_datasets' => $input['endpoint_datasets'],
            'endpoint_dataset' => $input['endpoint_dataset'],
            'run_time_hour' => $input['run_time_hour'],
            'run_time_minute' => $input['run_time_minute'],
            'enabled' => $input['enabled'],
            'tested' => array_key_exists('tested', $input) ? $input['tested'] : 0,
            'error' => false,
            'error_text' => null,
        ];

        $updateArray = $this->withFirstEnabledAt($federationId, $updateArray);

        Federation::where('id', $federationId)->update($updateArray);

        $this->updateSecrets($federationId, $input);

        $this->replaceNotifications($federationId, $input['notifications']);

        $response = $this->getWithNotifications($teamId, $federationId);

        $this->sendEmail($federationId, 'UPDATE');

        return $response;
    }

    public function clearErrorForTeam(int $teamId, int $federationId): void
    {
        Federation::whereHas('team', function ($query) use ($teamId) {
            $query->where('id', $teamId);
        })->where('id', $federationId)->update([
            'error' => false,
            'error_text' => null,
        ]);
    }

    public function delete(int $teamId, int $federationId, array $loggingContext = []): void
    {
        $federationNotifications = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->pluck('notification_id');

        foreach ($federationNotifications as $federationNotification) {
            Notification::where('id', $federationNotification)->delete();
            FederationHasNotification::where('notification_id', $federationNotification)->delete();
        }

        $federation = Federation::where('id', $federationId)->first();
        if ($federation && $federation->auth_secret_key_location) {
            try {
                app(GoogleSecretManagerService::class)->deleteSecret($federation->auth_secret_key_location);
            } catch (Exception $e) {
                \Log::info('failed to delete federation secret: ' . $e->getMessage(), $loggingContext);
            }
        }

        Federation::where('id', $federationId)->delete();

        TeamHasFederation::where([
            'federation_id' => $federationId,
            'team_id' => $teamId,
        ])->delete();
    }

    public function runNow(int $federationId): void
    {
        $checkFederation = Federation::where([
            'id' => $federationId,
            'enabled' => 1,
            'tested' => 1,
            'is_running' => 0,
        ])->first();
        if (is_null($checkFederation)) {
            throw new Exception('Federation not found!');
        }

        $service = new GatewayMetadataIngestionService();
        $service->setFederation($federationId);
        $gmi = $service->getActiveFederationsById();

        ProcessFederation::dispatch($gmi);
    }

    public function history(int $federationId, int $perPage): LengthAwarePaginator
    {
        $executions = FederationJobRun::executionsForFederation($federationId, $perPage);

        $executions->getCollection()->transform(function (FederationJobRun $execution) use ($federationId) {
            $rows = FederationJobRun::latestPerPidForExecution($federationId, $execution->job_uuid);

            $failed = $rows->filter(fn ($row) => $row->status === 0);
            $pending = $rows->filter(fn ($row) => is_null($row->status));

            $failedDatasets = $failed->map(fn ($row) => [
                'pid' => $row->pid,
                'message' => collect($row->errorMessages())
                    ->map(fn ($entry) => $entry['schema'] ? "{$entry['schema']}: {$entry['message']}" : $entry['message'])
                    ->implode('; '),
            ])->values()->all();

            $onlyFailure = count($failedDatasets) === 1 ? $failedDatasets[0] : null;

            if ($onlyFailure) {
                $status = 'failed';
                $message = $onlyFailure['message'];
            } elseif (count($failedDatasets) > 1) {
                $status = 'failed';
                $message = count($failedDatasets) . " of {$rows->count()} datasets failed";
            } elseif ($pending->count() > 0) {
                $status = 'in_progress';
                $message = null;
            } else {
                $status = 'success';
                $message = null;
            }

            return [
                'job_uuid' => $execution->job_uuid,
                'started_at' => $execution->started_at,
                'finished_at' => $execution->finished_at,
                'status' => $status,
                'message' => $message,
                'failed_datasets' => $failedDatasets,
            ];
        });

        return $executions;
    }

    public function sendEmail(int $federationId, string $type): void
    {
        $federation = Federation::where('id', $federationId)
            ->with(['team', 'notifications.userNotification'])
            ->first();
        if (is_null($federation)) {
            throw new Exception('Gateway App not found!');
        }

        $identifiers = [
            'CREATE' => 'federation.app.create',
            'UPDATE' => 'federation.app.update',
        ];

        if (!array_key_exists($type, $identifiers)) {
            throw new Exception('Send email type not found!');
        }

        $identifier = $identifiers[$type];

        $receivers = $this->sendEmailTo($federationId);

        foreach ($receivers as $receiver) {
            $to = [
                'to' => [
                    'email' => $receiver['email'],
                    'name' => $receiver['name'],
                ],
            ];

            $replacements = [
                '[[TEAM_ID]]' => $federation->team[0]['id'],
                '[[TEAM_NAME]]' => $federation->team[0]['name'],
                '[[USER_FIRSTNAME]]' => $receiver['firstname'],
                '[[FEDERATION_NAME]]' => 'Integration ' . $federation->federation_type,
                '[[FEDERATION_CREATED_AT_DATE]]' => $federation->created_at,
                '[[FEDERATION_UPDATED_AT_DATE]]' => $federation->updated_at,
                '[[FEDERATION_STATUS]]' => $federation->enabled ? 'enabled' : 'disabled',
                '[[CURRENT_YEAR]]' => date('Y'),
            ];

            app(EmailManager::class)->send($identifier, $to, $replacements);
        }
    }

    public function sendEmailTo(int $federationId): array
    {
        $return = [];

        $federation = Federation::where('id', $federationId)->first();
        if (is_null($federation)) {
            return $return;
        }

        $teamHasFederation = TeamHasFederation::where('federation_id', $federationId)->first();
        if (is_null($teamHasFederation)) {
            return $return;
        }
        $teamId = $teamHasFederation->team_id;

        // only for users with the following roles: 'custodian.team.admin', 'developer'
        $roles = Role::whereIn('name', ['custodian.team.admin', 'developer'])->select('id')->get();
        $roles = convertArrayToArrayWithKeyName($roles, 'id');
        $teamHasUsers = TeamHasUser::where('team_id', $teamId)->select('id', 'user_id')->get();

        $notificationuserId = [];
        foreach ($teamHasUsers as $item) {
            $teamUserHasRoles = TeamUserHasRole::whereIn('role_id', $roles)->where('team_has_user_id', $item->id)->first();
            if (!is_null($teamUserHasRoles)) {
                $notificationuserId[] = $item->user_id;
            }
        }

        $notificationuserId = array_unique($notificationuserId);
        $return = User::whereIn('id', $notificationuserId)->select(['firstname', 'name', 'email'])->get()->toArray();

        return $return;
    }

    /**
     * enabled_at records when a federation was *first* enabled: it is set once
     * and never cleared or overwritten by subsequent enable/disable cycles.
     */
    private function withFirstEnabledAt(int $federationId, array $updateArray): array
    {
        if (empty($updateArray['enabled'])) {
            return $updateArray;
        }

        $alreadyEnabled = Federation::where('id', $federationId)
            ->whereNotNull('enabled_at')
            ->exists();

        if (!$alreadyEnabled) {
            $updateArray['enabled_at'] = now();
        }

        return $updateArray;
    }

    private function updateSecrets(int $federationId, array $input): void
    {
        $secretsPayload = $this->getSecretsPayload($input);
        if (!$secretsPayload) {
            return;
        }

        try {
            $authSecretKeyLocation = $this->upsertFederationSecret($federationId, $secretsPayload);
        } catch (Exception $e) {
            throw new FederationSecretException('unable to update federation secret key', $e->getMessage());
        }

        Federation::where('id', $federationId)->update(['auth_secret_key_location' => $authSecretKeyLocation]);
    }

    private function replaceNotifications(int $federationId, array $notifications): void
    {
        $federationNotifications = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->pluck('notification_id');

        foreach ($federationNotifications as $federationNotification) {
            Notification::where('id', $federationNotification)->delete();
            FederationHasNotification::where('notification_id', $federationNotification)->delete();
        }

        foreach ($notifications as $notification) {
            // $notification may be a user id, or it may be an email address.
            $notification = Notification::create([
                'notification_type' => 'federation',
                'message' => '',
                'opt_in' => 0,
                'enabled' => 1,
                'email' => is_numeric($notification) ? null : $notification,
                'user_id' => is_numeric($notification) ? (int) $notification : null,
            ]);

            FederationHasNotification::create([
                'federation_id' => $federationId,
                'notification_id' => $notification->id,
            ]);
        }
    }

    private function getWithNotifications(int $teamId, int $federationId): ?Federation
    {
        return Federation::where('id', '=', $federationId)
            ->whereHas('team', function ($query) use ($teamId) {
                $query->where('id', $teamId);
            })->with(['notifications'])->first();
    }

    private function decryptAuthSecretKey(?string $secretLocation, ?string $authType): ?string
    {
        if (!$secretLocation || !in_array($authType, ['BEARER', 'API_KEY'])) {
            return null;
        }

        try {
            $payload = json_decode(app(GoogleSecretManagerService::class)->getSecret($secretLocation), true);
        } catch (Exception $e) {
            \Log::info('failed to retrieve federation secret ' . $secretLocation . ': ' . $e->getMessage());
            return null;
        }

        return match ($authType) {
            'BEARER' => $payload['bearer_token'] ?? null,
            'API_KEY' => $payload['api_key'] ?? null,
            default => null,
        };
    }

    private function upsertFederationSecret(int $federationId, array $secretsPayload): string
    {
        $federation = Federation::where('id', $federationId)->first();
        $gsms = app(GoogleSecretManagerService::class);

        if ($federation->auth_secret_key_location) {
            $gsms->addSecretVersion($federation->auth_secret_key_location, json_encode($secretsPayload));
            return $federation->auth_secret_key_location;
        }

        $authSecretKeyLocation = config('gateway.google_secrets_gmi_prepend_name') . $federationId;
        $gsms->createSecret($authSecretKeyLocation, json_encode($secretsPayload));

        return $authSecretKeyLocation;
    }

    private function getSecretsPayload(array $input): ?array
    {
        $secretsPayload = [];
        $secretKey = '';
        if (in_array($input['auth_type'], ['BEARER', 'API_KEY'])) {
            $secretKey = $input['auth_secret_key'];
        }
        switch ($input['auth_type']) {
            case 'BEARER':
                $secretsPayload = [
                    'bearer_token' => $secretKey,
                ];
                break;
            case 'API_KEY':
                $secretsPayload = [
                    'api_key' => $secretKey,
                    'client_id' => '', //something needs to happen here??
                    'client_secret' => '', //something needs to happen here??
                ];
                break;
            case 'NO_AUTH':
                $secretsPayload = null;
                break;
        }
        return $secretsPayload;
    }
}
