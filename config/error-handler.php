<?php

use HDRUK\ErrorHandler\Profiles\BuiltIn\AuthenticationExceptionProfile;
use HDRUK\ErrorHandler\Profiles\BuiltIn\AuthorizationExceptionProfile;
use HDRUK\ErrorHandler\Profiles\BuiltIn\ModelNotFoundProfile;
use HDRUK\ErrorHandler\Profiles\BuiltIn\NotFoundHttpExceptionProfile;
use HDRUK\ErrorHandler\Profiles\BuiltIn\QueryFailureProfile;
use HDRUK\ErrorHandler\Profiles\BuiltIn\ThrottleRequestsProfile;
use HDRUK\ErrorHandler\Profiles\BuiltIn\ValidationExceptionProfile;
use HDRUK\ErrorHandler\Profiles\FallbackExceptionProfile;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return [

    /*
    |--------------------------------------------------------------------
    | Reporting channels
    |--------------------------------------------------------------------
    |
    | Named channel instances. Each entry is an instance, not just a
    | type — e.g. "slack-critical" and "slack-warnings" are both the
    | "slack" driver with different webhook URLs, so routing rules below
    | can target them independently. Custom drivers registered via
    | ErrorHandler::extend() can be referenced here the same way.
    |
    | Every channel supports a per-channel `queue` override; when absent
    | the global `queue.enabled` default below applies.
    |
    */

    'channels' => [
        'log' => [
            'driver' => 'log',
            'channel' => 'stack',
        ],

        'gcp' => [
            'driver' => 'gcp',
            'stream' => env('ERROR_HANDLER_GCP_STREAM', 'php://stderr'),
        ],

        'slack-critical' => [
            'driver' => 'slack',
            'webhook_url' => env('ERROR_HANDLER_SLACK_CRITICAL_WEBHOOK', ''),
        ],

        'slack-warnings' => [
            'driver' => 'slack',
            'webhook_url' => env('ERROR_HANDLER_SLACK_WARNINGS_WEBHOOK', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------
    |
    | Decides which channel(s) a report is sent to, in this precedence
    | (highest first): the exception profile's own channels(), then
    | `exceptions` below (class => channels), then `status_routing`
    | (exact status code checked before its "4xx"/"5xx" bucket), then
    | `default`.
    |
    */

    'status_routing' => [
        429 => ['gcp'],
        '4xx' => ['gcp'],
        '5xx' => ['slack-critical', 'gcp'],
    ],

    /*
    | Route by exception class without needing a full profile.
    | e.g. \App\Exceptions\PaymentGatewayException::class => ['slack-critical'],
    */
    'exception_routing' => [
        //
    ],

    'default_channels' => ['log'],

    /*
    |--------------------------------------------------------------------
    | Per-environment overrides
    |--------------------------------------------------------------------
    |
    | When the current app environment matches a key here, its channel
    | list wins over everything above — e.g. never hit Slack/GCP locally,
    | stay silent in tests.
    |
    */

    'environments' => [
        'local' => ['channels' => ['log']],
        'testing' => ['channels' => []],
    ],

    /*
    |--------------------------------------------------------------------
    | Exception profiles
    |--------------------------------------------------------------------
    |
    | Maps an exception class to either a config array (see the docs for
    | available keys: code, status, message, channels, public_context,
    | internal_context, expose_public_context) or a class implementing
    | HDRUK\ErrorHandler\Contracts\ExceptionProfile. A mapping against a
    | base class is inherited by its subclasses.
    |
    */

    'exceptions' => [
        QueryException::class => QueryFailureProfile::class,
        PDOException::class => QueryFailureProfile::class,
        ValidationException::class => ValidationExceptionProfile::class,
        AuthenticationException::class => AuthenticationExceptionProfile::class,
        AuthorizationException::class => AuthorizationExceptionProfile::class,
        ModelNotFoundException::class => ModelNotFoundProfile::class,
        NotFoundHttpException::class => NotFoundHttpExceptionProfile::class,
        ThrottleRequestsException::class => ThrottleRequestsProfile::class,
    ],

    /*
    | Used for any exception with no mapping above.
    */
    'fallback' => FallbackExceptionProfile::class,

    /*
    |--------------------------------------------------------------------
    | Context enrichment
    |--------------------------------------------------------------------
    |
    | Everything here is data-minimising by default: request body,
    | header values (aside from the allowlist), and query string values
    | (aside from the allowlist) are excluded unless explicitly opted
    | into. Only the authenticated user/entity's primary key is included,
    | never their name/email.
    |
    */

    'context' => [
        'trace' => [
            'depth' => 10,
            'exclude' => ['*/vendor/*'],
            'allow' => [],
        ],

        'request' => [
            'header_allowlist' => ['X-Request-Id'],
            'query_allowlist' => [],
            'include_body' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------
    | Queueing
    |--------------------------------------------------------------------
    |
    | Reports are sent synchronously by default so a critical failure
    | (e.g. the DB is down) reaches Slack before the response returns to
    | the client. Set to true to dispatch every channel's send() as a
    | queued job on the app's default connection instead; override per
    | channel above with `'queue' => true|false`.
    |
    */

    'queue' => [
        'enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------
    | Correlation ID format
    |--------------------------------------------------------------------
    |
    | "ulid" (default, lexicographically sortable by time — nicer for
    | log searching) or "uuid".
    |
    */

    'correlation_id' => [
        'format' => 'ulid',
    ],

];
