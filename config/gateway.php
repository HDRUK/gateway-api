<?php

return [
    "audit_action_service" => env("AUDIT_ACTION_SERVICE", "gateway_api"),
    "daras_service" => env("DARAS_SERVICE", ""),
    "darq_service" => env("DARQ_SERVICE", ""),
    "feature_flagging_config_url" => env("FEATURE_FLAGGING_CONFIG_URL", ""),
    "gateway_url" => env("GATEWAY_URL", "http://localhost"),
    "google_application_project_path" => env("GOOGLE_APPLICATION_PROJECT_PATH", ""),
    "google_secrets_gmi_prepend_name" => env("GOOGLE_SECRETS_GMI_PREPEND_NAME", ""),
    "media_url" => env("MEDIA_URL", ""),
    "omop_seeding_nchunks" => env("OMOP_SEEDING_NCHUNKS", 500),
    "omop_seeding_use_infile" => env("OMOP_SEEDING_USE_INFILE", true),
    "rate_limit" => env("RATE_LIMIT", 2000),
    "scanning_filesystem_disk" => env("SCANNING_FILESYSTEM_DISK", "local_scan"),
    "search_service_url" => env("SEARCH_SERVICE_URL", 'http://localhost:8003'),
    "test_user_password" => env("TEST_USER_PASSWORD", "")
];
