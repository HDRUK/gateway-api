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
    ],

    'azure' => [
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect' => env('AZURE_REDIRECT_URL'),
        'proxy' => env('AZURE_PROXY')
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

    'media' => [
        'base_url' => env('MEDIA_URL', 'http://media.local'),
    ]
];
