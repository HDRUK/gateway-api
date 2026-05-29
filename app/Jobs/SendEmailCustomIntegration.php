<?php

namespace App\Jobs;

use App\Models\EmailTemplate;
use App\Models\Federation;
use App\Models\FederationJobRun;
use App\Models\Role;
use App\Models\User;
use App\Traits\GatewayMetadataIngestionTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmailCustomIntegration implements ShouldQueue
{
    use Queueable;
    use GatewayMetadataIngestionTrait;

    private int $federationId;
    private ?string $jobUuid;

    /**
     * Create a new job instance.
     */
    public function __construct(int $federationId, ?string $jobUuid)
    {
        $this->federationId = $federationId;
        $this->jobUuid = $jobUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->jobUuid) {
            return;
        }

        $federation = $this->getDetails($this->federationId);
        $team = $federation['team'];
        $teamId = $team[0]['id'];
        $teamName = $team[0]['name'];
        $notifications = $federation['notifications'];

        $integrationDetails = $this->getFederationHistory($teamId);

        $integrationSuccessList = $integrationDetails['integration_success'];
        $integrationErrorsList = $integrationDetails['integration_errors'];

        $template = null;

        foreach ($notifications as $notification) {
            $userId = (isset($notification['user_notification']) && !is_null($notification['user_notification'])) ? $notification['user_notification']['id'] : null;
            if (is_null($userId)) {
                $this->log('warning', 'send email after integration: user id not found');
                continue;
            }

            $userEmail = $notification['user_notification']['preferred_email'] === 'primary' ? $notification['user_notification']['email'] : $notification['user_notification']['secondary_email'];

            if (is_null($userEmail)) {
                $this->log('warning', 'send email after integration: email not found');
                continue;
            }

            $checkUser = $this->checkUserPerms($userId, $teamId);
            if ($checkUser) {
                $template = EmailTemplate::where('identifier', '=', 'integration.job.fails.teamadmin_developer')->first();
            }

            if (!$checkUser) {
                $template = EmailTemplate::where('identifier', '=', 'integration.job.fails.no_teamadmin_developer')->first();
            }

            $to = [
                'to' => [
                    'email' => $userEmail,
                    'name' => $notification['user_notification']['name'],
                ],
            ];

            $replacements = [
                '[[USER_FIRSTNAME]]' => $notification['user_notification']['firstname'],
                '[[TEAM_NAME]]' => $teamName,
                '[[DATE_OF_ERROR]]' => Carbon::now()->toDateTimeString(),
                '[[INTEGRATION_LIST_URL]]' => config('gateway.gateway_url') . "/en/account/team/{$teamId}/integrations/integration/list",
                '[[INTEGRATION_SUCCESS]]' => $integrationSuccessList,
                '[[INTEGRATION_ERRORS]]' => $integrationErrorsList,
                '[[USER_LIST]]' => $checkUser ? '' : $this->getListOfUsers($teamId),
            ];

            SendEmailJob::dispatch($to, $template, $replacements);
        }
    }

    public function getFederationHistory(int $teamId)
    {
        $pids = $this->getUniquePid();

        $integrationSuccess = "<ul>";
        $integrationErrors = "<ul>";

        foreach ($pids as $pid) {
            $latestAttempt = FederationJobRun::where('job_uuid', $this->jobUuid)
                ->where('pid', $pid)
                ->latest()
                ->first();

            if ($latestAttempt->status === 1) {
                $details = data_get($latestAttempt, 'details.message', '');
                $integrationSuccess .= "<li>PID: {$latestAttempt->pid} - {$details}</li>";
            }

            if ($latestAttempt->status === 0) {
                $details = data_get($latestAttempt, 'details.message', []);
                $error = $this->getErrors($details);
                $integrationErrors .= "<li>PID - {$latestAttempt->pid}:<br>{$error}</li>";
            }
        }

        $integrationSuccess .= "</ul>";
        $integrationErrors .= "<ul>";

        return [
            'integration_success' => $integrationSuccess,
            'integration_errors' => $integrationErrors
        ];
    }

    public function getDetails(int $fedId)
    {
        return Federation::query()
            ->where('id', $fedId)
            ->with(['team', 'notifications.userNotification'])
            ->first()
            ->toArray();
    }

    public function checkUserPerms(int $userId, int $teamId): bool
    {
        return Role::whereIn('name', ['custodian.team.admin', 'developer'])
            ->whereIn('id', function ($query) use ($userId, $teamId) {
                $query->select('tuhr.role_id')
                    ->from('team_user_has_roles as tuhr')
                    ->join('team_has_users as thu', 'thu.id', '=', 'tuhr.team_has_user_id')
                    ->where('thu.team_id', $teamId)
                    ->where('thu.user_id', $userId);
            })
            ->exists();
    }

    public function getListOfUsers(int $teamId): string
    {
        $users = User::whereIn('id', function ($query) use ($teamId) {
            $query->select('thu.user_id')
                ->from('team_has_users as thu')
                ->join('team_user_has_roles as tuhr', 'tuhr.team_has_user_id', '=', 'thu.id')
                ->join('roles as r', 'r.id', '=', 'tuhr.role_id')
                ->where('thu.team_id', $teamId)
                ->whereIn('r.name', ['custodian.team.admin', 'developer']);
        })->pluck('name');

        if ($users->isEmpty()) {
            return '';
        }

        return '<ul>' . $users->map(fn ($name) => "<li>{$name}</li>")->implode('') . '</ul>';
    }

    public function getUniquePid()
    {
        return FederationJobRun::select('pid')
            ->where('job_uuid', $this->jobUuid)
            ->where('federation_id', $this->federationId)
            ->distinct()
            ->pluck('pid')->toArray();
    }

    public function getErrors(array $errors)
    {
        $string = '';

        foreach ($errors as $err) {
            $stringSchema = "{$err['name']}/{$err['version']}";
            foreach ($err['errors'] as $item) {
                $string .= "{$stringSchema}  - {$item['message']}<br>";
            }
        }

        return $string;
    }
}
