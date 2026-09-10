<?php

use HDRUK\ErrorHandler\ErrorHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    // Application::configure() enables Laravel's automatic event-listener
    // discovery by default (scanning app/Listeners for handle() methods
    // typed to an event). App\Providers\EventServiceProvider already
    // explicitly maps every listener and opts out via
    // shouldDiscoverEvents(), so leaving this on double-registers every
    // discoverable listener (e.g. ProcessFederationFailure fired twice
    // per event).
    ->withEvents(discover: false)
    ->withRouting(
        using: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::prefix('api/v1')
                ->middleware('api')
                ->group(base_path('routes/api.v1.php'));

            Route::prefix('api/v2')
                ->middleware('api')
                ->group(base_path('routes/api.v2.php'));

            Route::prefix('api/v3')
                ->middleware('api')
                ->group(base_path('routes/api.v3.php'));

            Route::prefix('api/services')
                ->middleware('api')
                ->group(base_path('routes/api.services.php'));

            Route::prefix('api/scheduler')
                ->middleware('api')
                ->group(base_path('routes/api.scheduler.php'));
        },
        commands: base_path('routes/console.php'),
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            \App\Http\Middleware\BlockWebSocket::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\Cors::class,
            \App\Http\Middleware\TrustProxies::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            \App\Http\Middleware\ProfileRequest::class,
            \App\Http\Middleware\HstsMiddleware::class,
            //\App\Http\Middleware\ValidateRequestID::class,
            \App\Http\Middleware\LogRequestResponse::class,
        ]);

        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->group('api', [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ResolveGwdmVersionContext::class,
            // \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'jwt.verify' => \App\Http\Middleware\JwtMiddleware::class,
            'sanitize.input' => \App\Http\Middleware\SanitizeMiddleware::class,
            'check.access' => \App\Http\Middleware\CheckAccessMiddleware::class,
            'check.access.userId' => \App\Http\Middleware\CheckUserIdMatches::class,
            'sunset' => \App\Http\Middleware\SunsetHeader::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // // Runs this command daily at midnight
        // $schedule->command('app:cohort-user-expiry')->dailyAt('02:00');

        // // runs the ARS email scanner
        // // $schedule->command('app:alias-reply-scanner')->everyFiveMinutes();
        // $schedule->job(new \App\Jobs\AliasReplyScannerJob())->everyFiveMinutes();

        // // update license information from EU server
        // $schedule->command('app:update-licenses')->monthlyOn(1, '01:00');

        // // update hubspot contacts information
        // $schedule->command('app:sync-hubspot-contacts')->dailyAt('04:00');

        // nightly check that active dataset pages are reachable
        // $schedule->command('app:nightly-dataset-test')->dailyAt('03:00');

        // nightly check for dead links in active dataset metadata, emails team admins a report
        // $schedule->command('app:nightly-dataset-link-check')->dailyAt('03:30');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Reporting (correlation IDs, channel routing/log/GCP output) goes
        // through the package. The client-facing response is deliberately
        // NOT routed through it yet — kept byte-for-byte identical to the
        // pre-package shape ({code: <int status>, message}) to avoid
        // changing the API's response contract until that's a deliberate,
        // separate decision.
        $exceptions->reportable(function (Throwable $e) {
            app(ErrorHandler::class)->handleReport($e);

            return false;
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            $statusCode = 500;

            if ($e->getCode()) {
                $statusCode = (int) $e->getCode();
            }

            $response = [
                'code' => $statusCode,
                'message' => $e->getMessage(),
            ];

            if (Config::get('app.debug')) {
                $response['details'] = [
                    'exception' => get_class($e),
                    'trace' => $e->getTrace(),
                ];
            }

            return response()->json($response, $statusCode);
        });
    })->create();
