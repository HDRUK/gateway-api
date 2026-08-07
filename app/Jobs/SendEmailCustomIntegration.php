<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\Federation;
use App\Models\FederationJobRun;
use App\Services\EmailManager;
use App\Traits\GatewayMetadataIngestionTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmailCustomIntegration implements ShouldQueue
{
    use Queueable;
    use GatewayMetadataIngestionTrait;

    public $tries   = 3;
    public $backoff = [10, 30, 60];

    private int $federationId;
    private ?string $jobUuid;
    private string $outcome;
    private ?string $errorMessage;

    public function __construct(int $federationId, ?string $jobUuid, string $outcome, ?string $errorMessage = null)
    {
        $this->onQueue('high');
        $this->federationId = $federationId;
        $this->jobUuid      = $jobUuid;
        $this->outcome      = $outcome;
        $this->errorMessage = $errorMessage;
    }

    public function handle(): void
    {
        $federation    = $this->getDetails($this->federationId);
        $team          = $federation['team'];
        $teamId        = $team[0]['id'];
        $teamName      = $team[0]['name'];
        $notifications = $federation['notifications'];

        $history = $this->jobUuid
            ? $this->getFederationHistory()
            : ['integration_success' => '<ul></ul>', 'integration_errors' => '<ul></ul>', 'success_count' => 0];

        if ($this->outcome === 'success' && $history['success_count'] === 0) {
            return;
        }

        foreach ($notifications as $notification) {
            $userId = $notification['user_notification']['id'] ?? null;

            if (is_null($userId)) {
                $this->log('warning', 'send email after integration: user id not found');
                continue;
            }

            $userEmail = $notification['user_notification']['preferred_email'] === 'primary'
                ? $notification['user_notification']['email']
                : $notification['user_notification']['secondary_email'];

            if (is_null($userEmail)) {
                $this->log('warning', 'send email after integration: email not found');
                continue;
            }

            $checkUser      = $this->checkUserPerms($userId, $teamId);
            $permSuffix     = $checkUser ? 'teamadmin_developer' : 'no_teamadmin_developer';
            $templateId     = "integration.job.{$this->outcome}.{$permSuffix}";

            $to = [
                'to' => [
                    'email' => $userEmail,
                    'name'  => $notification['user_notification']['name'],
                ],
            ];

            $replacements = [
                '[[USER_FIRSTNAME]]'       => $notification['user_notification']['firstname'],
                '[[TEAM_NAME]]'            => $teamName,
                '[[RUN_DATE]]'             => Carbon::now()->toDateTimeString(),
                '[[INTEGRATION_ID]]'       => $federation['id'],
                '[[INTEGRATION_LIST_URL]]' => config('gateway.gateway_url') . "/en/account/team/{$teamId}/integrations/integration/list",
                '[[DATASET_COUNT]]'        => (string) $history['success_count'],
                '[[INTEGRATION_SUCCESS]]'  => $history['integration_success'],
                '[[INTEGRATION_ERRORS]]'   => $history['integration_errors'],
                '[[USER_LIST]]'            => $checkUser ? '' : $this->getListOfUsers($teamId),
                '[[JOB_ERROR]]'            => $this->errorMessage ?? '',
            ];

            app(EmailManager::class)->send($templateId, $to, $replacements);
        }
    }

    public function getFederationHistory(): array
    {
        $latestAttempts = FederationJobRun::latestPerPidForExecution($this->federationId, $this->jobUuid);
        $titlesByPid    = Dataset::titlesForPids($latestAttempts->pluck('pid')->all());

        $integrationSuccess = '<ul>';
        $integrationErrors  = '<ul>';
        $successCount       = 0;

        foreach ($latestAttempts as $latestAttempt) {
            $title = $titlesByPid[$latestAttempt->pid];

            if ($latestAttempt->status === 1) {
                $details    = data_get($latestAttempt, 'details.message', '');
                $identifier = $title ? "{$title} (PID: {$latestAttempt->pid})" : "PID: {$latestAttempt->pid}";
                $integrationSuccess .= "<li>{$identifier} - {$details}</li>";
                $successCount++;
            }

            if ($latestAttempt->status === 0) {
                $error = collect($latestAttempt->errorMessages())
                    ->map(fn ($entry) => $entry['schema'] ? "{$entry['schema']}  - {$entry['message']}<br>" : $entry['message'])
                    ->implode('');
                $identifier = $title ? "{$title} (PID: {$latestAttempt->pid})" : "PID - {$latestAttempt->pid}";
                $integrationErrors .= "<li>{$identifier}:<br>{$error}</li>";
            }
        }

        $integrationSuccess .= '</ul>';
        $integrationErrors  .= '</ul>';

        return [
            'integration_success' => $integrationSuccess,
            'integration_errors'  => $integrationErrors,
            'success_count'       => $successCount,
        ];
    }

    public function getDetails(int $fedId): array
    {
        return Federation::query()
            ->where('id', $fedId)
            ->with(['team', 'notifications.userNotification'])
            ->first()
            ->toArray();
    }

    public function checkUserPerms(int $userId, int $teamId): bool
    {
        return (bool) \DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM roles r
                INNER JOIN team_user_has_roles tuhr ON tuhr.role_id = r.id
                INNER JOIN team_has_users thu ON thu.id = tuhr.team_has_user_id
                WHERE r.name IN ('custodian.team.admin', 'developer')
                AND thu.team_id = :team_id
                AND thu.user_id = :user_id
            ) AS result
        ", [
            'team_id' => $teamId,
            'user_id' => $userId,
        ])->result;
    }

    public function getListOfUsers(int $teamId): string
    {
        $users = \DB::select("
            SELECT u.name
            FROM users u
            INNER JOIN team_has_users thu ON thu.user_id = u.id
            INNER JOIN team_user_has_roles tuhr ON tuhr.team_has_user_id = thu.id
            INNER JOIN roles r ON r.id = tuhr.role_id
            WHERE thu.team_id = :team_id
            AND r.name IN ('custodian.team.admin', 'developer')
        ", [
            'team_id' => $teamId,
        ]);

        if (empty($users)) {
            return '';
        }

        return '<ul>' . collect($users)->map(fn ($user) => "<li>{$user->name}</li>")->implode('') . '</ul>';
    }

}
