<?php

namespace App\Console\Commands;

use App\Models\CohortRequest;
use App\Models\CohortRequestHasLog;
use App\Models\CohortRequestHasPermission;
use App\Models\CohortRequestLog;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CohortPreprodUsersRestore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cohort-preprod-users-restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the users with preprod only access to cohort discovery from file.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Set all migrated requests to "BOTH" - because they will all have been migrated from prod
        CohortRequest::where('request_status', 'APPROVED')
            ->update([
                'access_to_env' => 'BOTH'
            ]);

        // Load data from the file
        $cohortUsersFile = Storage::disk('local')->get('cohort_preprod_users.json');
        $cohortUsers = json_decode($cohortUsersFile, true);

        foreach ($cohortUsers as $cohortUser) {
            // Identify the new user id
            if (is_null($cohortUser['user'])) {
                echo 'Cohort request with id ' . $cohortUser['id'] . ' has no user - skipping.';
                continue;
            }
            $userEmail = $cohortUser['user']['email'];
            $matchUser = User::where('email', $userEmail)->first();

            if ($matchUser) {
                $cohortRequest = CohortRequest::where('user_id', $matchUser->id)->first();

                if (!$cohortRequest) {
                    $this->addCohortRequest($cohortUser, $matchUser->id);
                    continue;
                }

                // If the user had a non-approved request on prod - override status with preprod value
                if (($cohortRequest) && ($cohortRequest->request_status !== 'APPROVED')) {
                    $cohortRequest->request_status = $cohortUser['request_status'];
                    $cohortRequest->access_to_env = 'PREPROD';
                    $cohortRequest->save();
                }

            } else {
                $newUser = User::create($cohortUser['user']);
                $this->addCohortRequest($cohortUser, $newUser->id);
            }

        }

    }

    private function addCohortRequest(array $cohortRequest, int $userId): void
    {
        $permission = Permission::where([
            'name' => 'GENERAL_ACCESS',
            'application' => 'cohort',
        ])->first();

        if (!$permission) {
            echo 'Cohort access permission not found!';
        }

        $cohortRequest['user_id'] = $userId;
        $cohortRequest['access_to_env'] = 'PREPROD';
        $request = CohortRequest::create($cohortRequest);
        CohortRequestHasPermission::create([
            'cohort_request_id' => $request->id,
            'permission_id' => $permission->id,
        ]);

        foreach ($cohortRequest['logs'] as $log) {
            $log['user_id'] = $userId;
            $newLog = CohortRequestLog::create($log);
            CohortRequestHasLog::create([
                'cohort_request_id' => $request->id,
                'cohort_request_log_id' => $newLog->id
            ]);
        }

    }
}
