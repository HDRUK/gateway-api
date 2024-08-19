<?php

namespace Database\Demo;

use Exception;
use App\Models\CohortRequest;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use App\Models\CohortRequestLog;
use App\Models\CohortRequestHasLog;
use Illuminate\Support\Facades\Http;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CohortRequestDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // init status
        $initRequests = [
            // will be a request in pending status
            [
                'user_id' => 3,
                'status' => 'PENDING',
                'details' => 'As a key contributor to our department\'s business continuity efforts, I need access to the BCP request application to efficiently submit updates and ensure the accuracy of our plans.',
            ],

            // will be a request in expired status
            [
                'user_id' => 4,
                'status' => 'PENDING',
                'details' => 'Access to the BCP request application will enhance collaboration by enabling real-time communication with other stakeholders, ensuring a coordinated approach to business continuity planning across departments.',
            ],

            // will be a request in active status
            [
                'user_id' => 5,
                'status' => 'PENDING',
                'details' => 'Direct access to the BCP request application is essential for quick response to incidents or changes, allowing me to promptly update and adapt our business continuity plans to maintain their effectiveness.',
            ],
        ];

        foreach ($initRequests as $cr) {
            $cohortRequest = CohortRequest::create([
                'user_id' => $cr['user_id'],
                'request_status' => $cr['status'],
                'cohort_status' => false,
            ]);

            $cohortRequestLog = CohortRequestLog::create([
                'user_id' => $cr['user_id'],
                'details' => $cr['details'],
                'request_status' => $cr['status'],
            ]);

            CohortRequestHasLog::create([
                'cohort_request_id' => $cohortRequest->id,
                'cohort_request_log_id' => $cohortRequestLog->id,
            ]);
        }

        // moved id = 2 in expired status
        $cohortRequestEx = CohortRequest::where(['id' => 2])
            ->update([
                'request_status' => 'EXPIRED',
                'cohort_status' => false,
                'request_expire_at' => Carbon::now(),
            ]);
        $cohortRequestLogEx = CohortRequestLog::create([
            'user_id' => 1,
            'details' => 'Access expired',
            'request_status' => 'EXPIRED',
        ]);

        CohortRequestHasLog::create([
            'cohort_request_id' => 2,
            'cohort_request_log_id' => $cohortRequestLogEx->id,
        ]);

        // moved id = 3 in active status
        $cohortRequestId = 3;
        $payload = [
            'request_status' => 'APPROVED',
            'details' => 'As the designated approver for BCP request application access, I am responsible for verifying that individuals seeking access adhere to our security policies. This includes confirming their role in business continuity planning and ensuring that granting access aligns with the principle of least privilege.',
        ];

        try {
            CohortRequest::where(['id' => $cohortRequestId])->update($payload);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
