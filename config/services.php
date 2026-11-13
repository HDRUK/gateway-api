<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URL'),
        'open_id' => true,
    ],

    'openathens' => [
        'client_id' => env('OPENATHENS_CLIENT_ID'),
        'client_secret' => env('OPENATHENS_CLIENT_SECRET'),
        'issuer' => env('OPENATHENS_ISSUER_URL'),
        'open_id' => true,
        'redirect' => env('OPENATHENS_REDIRECT_URL'),
        'dta_redirect' => env('OPENATHENS_REDIRECT_DTA_URL'),
    ],

    'azure' => [
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect' => env('AZURE_REDIRECT_URL'),
        'proxy' => env('AZURE_PROXY'),
    ],

    'googlepubsub' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'gcp_pubsub_project_id'),
        'pubsub_topic' => env('GOOGLE_CLOUD_PUBSUB_TOPIC', 'gcp_pubsub_pubsub_topic'),
        'enabled' => env('GOOGLE_CLOUD_PUBSUB_ENABLED', false),
    ],

    'googlelogging' => [
        'project_id' => env('GOOGLE_CLOUD_LOGGING_PROJECT_ID', 'gcp_pubsub_project_id'),
        'log_name' => env('GOOGLE_CLOUD_LOGGING_NAME', 'gateway-api'),
        'enabled' => env('GOOGLE_CLOUD_LOGGING_ENABLED', false),
    ],

    'googlebigquery' => [
        'project_id' => env('GOOGLE_CLOUD_BIGQUERY_PROJECT_ID', 'gcp_bigquery_project_id'),
        'dashboard_dataset' => env('GOOGLE_CLOUD_BIGQUERY_DASHBOARD_DATASET', 'gcp_bigquery_dashboard_dataset'),
        'dashboard_table' => env('GOOGLE_CLOUD_BIGQUERY_DASHBOARD_TABLE', 'gcp_bigquery_dashboard_table'),
        'search_dataset' => env('GOOGLE_CLOUD_BIGQUERY_SEARCH_DATASET', 'search'),
        'search_analytics_table' => env('GOOGLE_CLOUD_BIGQUERY_SEARCH_ANALYTICS_TABLE', 'search_analytics'),
    ],

    'elasticclient' => [
        'verify_ssl' => env('ELASTICSEARCH_VERIFY_SSL', false),
        'user' => env('ELASTICSEARCH_USER'),
        'password' => env('ELASTICSEARCH_PASS'),
        'timeout' => env('ELASTICSEARCH_TIMEOUT', 10),
    ],

    'hubspot' => [
        'enabled' => env('HUBSPOT_ENABLED', false),
        'base_url' => env('HUBSPOT_BASE_URL', 'http://hub.local'),
        'key' => env('HUBSPOT_KEY', 'hubspot_key'),
    ],

    'rquest' => [
        'init_url' => env('RQUEST_INIT_URL', 'http://rquest.local'),
    ],

    'cohort_discovery_service' => [
        'init_url' => env('COHORT_DISCOVERY_URL'),
        'auth_url' => env('COHORT_DISCOVERY_AUTH_URL'),
        'service_account' => env('COHORT_DISCOVERY_SERVICE_ACCOUNT', 'cohort-service@hdruk.ac.uk'),
    ],

    'traser' => [
        'url' => env('TRASER_SERVICE_URL', 'http://localhost:8002'),
    ],

    'mjml' => [
        'render_url' => env('MJML_RENDER_URL', ''),
    ],

    'clamav' => [
        'api_url' => env('CLAMAV_API_URL', 'http://clamav:3001'),
        'basic_auth_username' => env('CLAMAV_BASIC_AUTH_USERNAME', ''),
        'basic_auth_password' => env('CLAMAV_BASIC_AUTH_PASSWORD', ''),
    ],

    'dta' => [
        'api_url' => env('DTA_API_URL', ''),
        'url' => env('DTA_URL', ''),
    ],

    // Safe People Registry acting as an identity source. The registry's own
    // Keycloak client is never contacted directly by Gateway - the browser is
    // sent to the Registry web app, which redeems the auth code itself and
    // hands Gateway a short-lived, single-use code via its API instead.
    'registry' => [
        'web_url' => env('REGISTRY_WEB_URL', ''),
        'login_path' => env('REGISTRY_WEB_LOGIN_PATH', '/en/keycloak'),
        'api_url' => env('REGISTRY_API_URL', ''),
        'handoff_secret' => env('REGISTRY_HANDOFF_SECRET'),
    ],

    'mauro' => [
        'app_key' => env('MAURO_APP_KEY'),
        'api_url' => env('MAURO_API_URL'),
    ],

    'media' => [
        'base_url' => env('MEDIA_URL', 'http://media.local'),
    ],

    'slack' => [
        'feature_flag_webhook_url' => env('SLACK_FEATURE_FLAG_WEBHOOK_URL', ''),
    ],

    'jsm' => [
        'base_url' => env('JSM_BASE_URL', 'https://api.atlassian.com/ex/jira'),
        'email' => env('JSM_EMAIL'),
        'api_token' => env('JSM_API_TOKEN'),
        'cloud_id' => env('JSM_CLOUD_ID'),
        'service_desk_id' => env('JSM_SERVICE_DESK_ID', '7'),
        'request_type_id' => env('JSM_REQUEST_TYPE_ID', '78'),
        // Feedback Log (desk 7) > Gateway Enquiry (request type 78) fields.
        'fields' => [
            'enquiry_type' => ['id' => 'customfield_10752', 'share_feedback' => '11728'],
            'future_contact' => ['id' => 'customfield_11447', 'no' => '13138'],
            'user_type' => ['id' => 'customfield_10223', 'user' => '10397', 'data_custodian' => '10394'],
        ],
    ],
];
