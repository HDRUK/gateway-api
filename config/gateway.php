<?php

return [
    "audit_action_service" => env("AUDIT_ACTION_SERVICE", "gateway_api"),
    "daras_service" => env("DARAS_SERVICE", ""),
    "darq_service" => env("DARQ_SERVICE", ""),
    "feature_flagging_config_url" => env("FEATURE_FLAGGING_CONFIG_URL", ""),
    "gateway_url" => env("GATEWAY_URL", "http://localhost"),
    "google_secrets_gmi_prepend_name" => env("GOOGLE_SECRETS_GMI_PREPEND_NAME", ""),
    "media_url" => env("MEDIA_URL", ""),
    "nightly_dataset_link_check_concurrency" => env("NIGHTLY_DATASET_LINK_CHECK_CONCURRENCY", 5),
    "nightly_dataset_link_check_http_batch_size" => env("NIGHTLY_DATASET_LINK_CHECK_HTTP_BATCH_SIZE", 10),
    "nightly_dataset_test_concurrency" => env("NIGHTLY_DATASET_TEST_CONCURRENCY", 5),
    "omop_seeding_nchunks" => env("OMOP_SEEDING_NCHUNKS", 500),
    "omop_seeding_use_infile" => env("OMOP_SEEDING_USE_INFILE", true),
    "rate_limit" => env("RATE_LIMIT", 2000),
    "scanning_filesystem_disk" => env("SCANNING_FILESYSTEM_DISK", "local_scan"),
    "search_deferred_ttl" => (int) env("SEARCH_DEFERRED_TTL", 60),
    "search_service_url" => env("SEARCH_SERVICE_URL", 'http://localhost:8003'),
    "test_user_password" => env("TEST_USER_PASSWORD", "")
];
