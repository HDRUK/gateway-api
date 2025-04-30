<?php

return [
    'cohort_access_expiry_warning_times_in_days' => env('COHORT_ACCESS_EXPIRY_WARNING_TIMES_IN_DAYS', '1,7,14'),
    'cohort_access_expiry_time_in_days' => env('COHORT_ACCESS_EXPIRY_TIME_IN_DAYS', 180),
    'cohort_discovery_access_url' => env('COHORT_DISCOVERY_ACCESS_URL', '#'),
    'cohort_discovery_using_url' => env('COHORT_DISCOVERY_USING_URL', '#'),
    'cohort_discovery_renew_url' => env('COHORT_DISCOVERY_RENEW_URL', '#'),
];
