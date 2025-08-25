<?php

return [
    "GWDM" => [
        "name" => env("GWDM", "GWDM"),
        "version" => env("GWDM_CURRENT_VERSION", "2.0")
    ],
    'google_project_path' => env('GOOGLE_APPLICATION_PROJECT_PATH', ''),
    'system_user_id' => env('GATEWAY_API_USER_ID'),
];
