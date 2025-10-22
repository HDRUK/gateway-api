<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
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

            Route::prefix('api/services')
                ->middleware('api')
                ->group(base_path('routes/api.services.php'));

            Route::prefix('api/scheduler')
                ->middleware('api')
                ->group(base_path('routes/api.scheduler.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // TODO - Change this when we're ready to introduce rate limiting proper
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('RATE_LIMIT', 2000))->by($request->user()?->id ?: $request->ip());
        });
    }
}
