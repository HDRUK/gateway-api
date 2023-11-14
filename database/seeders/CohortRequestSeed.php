<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasLog;
use App\Models\CohortRequestLog;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CohortRequestSeed extends Seeder
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

            if (!$checkRequestByUserId) {
                $cohortRequest = CohortRequest::create([
                    'user_id' => $userId,
                    'request_status' => 'PENDING',
                    'cohort_status' => false,
                    'request_expire_at' => Carbon::now()->addSeconds(env('COHORT_REQUEST_EXPIRATION')),
                ]);

                $cohortRequestLog = CohortRequestLog::create([
                    'user_id' => $userId,
                    'details' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                    'request_status' => 'PENDING',
                ]);

                CohortRequestHasLog::create([
                    'cohort_request_id' => $cohortRequest->id,
                    'cohort_request_log_id' => $cohortRequestLog->id,
                ]);
            }
        }
    }
}
