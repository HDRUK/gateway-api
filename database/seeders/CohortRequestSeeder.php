<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Permission;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasLog;
use App\Models\CohortRequestHasPermission;
use App\Models\CohortRequestLog;
use Illuminate\Database\Seeder;

class CohortRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $userId = User::all()->random()->id;

            $checkRequestByUserId = CohortRequest::where([
                'user_id' => $userId,
            ])->first();

            $status = fake()->randomElement(['PENDING', 'APPROVED', 'REJECTED', 'BANNED','SUSPENDED','EXPIRED']);

            if (!$checkRequestByUserId) {
                $cohortRequest = CohortRequest::create([
                    'created_at' => fake()->dateTimeBetween('-7 month', 'now'),
                    'user_id' => $userId,
                    'request_status' => $status,
                    'cohort_status' => ($status === 'PENDING' ? false : true),
                    'request_expire_at' => null,
                    'accept_declaration' => ($status === 'APPROVED' ? true : false), // Simulating the response from the front
                ]);

                if ($status === 'APPROVED') {
                    $permissions = Permission::where([
                        'application' => 'cohort',
                        'name' => 'GENERAL_ACCESS',
                    ])->first();
                    CohortRequestHasPermission::create([
                        'cohort_request_id' => $cohortRequest->id,
                        'permission_id' => $permissions->id,
                    ]);
                }

                $cohortRequestLog = CohortRequestLog::create([
                    'user_id' => $userId,
                    'details' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                    'request_status' => $status,
                ]);

                CohortRequestHasLog::create([
                    'cohort_request_id' => $cohortRequest->id,
                    'cohort_request_log_id' => $cohortRequestLog->id,
                ]);
            } else {
                // Handle status change logic here
                $newStatus = fake()->randomElement(['REJECTED', 'BANNED', 'SUSPENDED', 'EXPIRED']);
                if (in_array($checkRequestByUserId->request_status, ['APPROVED']) && in_array($newStatus, ['REJECTED', 'BANNED', 'SUSPENDED', 'EXPIRED'])) {
                    $checkRequestByUserId->update([
                        'request_status' => $newStatus,
                        'accept_declaration' => false,
                    ]);

                    // Log the status change
                    $cohortRequestLog = CohortRequestLog::create([
                        'user_id' => $userId,
                        'details' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                        'request_status' => $newStatus,
                    ]);

                    CohortRequestHasLog::create([
                        'cohort_request_id' => $checkRequestByUserId->id,
                        'cohort_request_log_id' => $cohortRequestLog->id,
                    ]);
                }
            }
        }
    }
}
