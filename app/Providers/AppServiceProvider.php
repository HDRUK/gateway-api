<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserHasRole;
use App\Observers\UserHasRoleObserver;
use App\Observers\UserObserver;
use App\Http\Controllers\SSO\CustomAccessToken;
use App\Models\DataAccessApplication;
use App\Observers\DataAccessApplicationObserver;
use App\Services\Gwdm\GwdmHandlerFactory;
use App\Services\Gwdm\GwdmMetadataHandler;
use Config;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Pennant\Feature;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(GwdmMetadataHandler::class, fn ($app) => $app->make(GwdmHandlerFactory::class)
            ->resolve(Config::get('metadata.GWDM.version')));

        if (Config::get('logging.sqlLog') === true) {
            \DB::listen(function ($query) {
                $bindings = [];
                foreach ($query->bindings as $i => $binding) {
                    if ($binding instanceof \DateTime) {
                        $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                    } elseif (is_string($binding)) {
                        $bindings[$i] = "'$binding'";
                    } else {
                        $bindings[$i] = "'$binding'";
                    }
                }

                $sql = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
                $sql = vsprintf($sql, $bindings);
                \Log::warning('SQL query: '.$sql, ['time' => $query->time]);
            });
        }

        // Passport::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Passport::tokensCan([
            'openid' => 'openid',
            'email' => 'email',
            'profile' => 'profile',
            'rquestroles' => 'rquestroles',
            'cohort_discovery_roles' => 'cohort_discovery_roles',
        ]);

        if (config('app.app_token_variant') === 'gateway') {
            Passport::useAccessTokenEntity(CustomAccessToken::class);
        }

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        DataAccessApplication::observe(DataAccessApplicationObserver::class);
        User::observe(UserObserver::class);
        UserHasRole::observe(UserHasRoleObserver::class);

        Feature::resolveScopeUsing(fn ($driver) => null);

        // Resolved identity lives on the attributes bag, which only the
        // application can write to - never the input bag, which the client
        // fills. Returns [] when unauthenticated; call sites that need the
        // older ['id' => null] shape append `?: ['id' => null]`.
        Request::macro('jwtUser', function () {
            return $this->attributes->get('jwt_user', []);
        });

        $this->configureRateLimiting();
    }

    /**
     * @return void
     */
    protected function configureRateLimiting()
    {
        // TODO - Change this when we're ready to introduce rate limiting proper
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('gateway.rate_limit'))->by($request->user()?->id ?: $request->ip());
        });
    }
}
