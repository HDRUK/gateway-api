<?php

namespace App\Providers;

use Config;
use Laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\SSO\CustomAccessToken;
use App\Models\TeamHasDataAccessApplication;
use App\Observers\TeamHasDataAccessApplicationObserver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
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

                $sql = str_replace(array('%', '?'), array('%%', '%s'), $query->sql);
                $sql = vsprintf($sql, $bindings);
                \Log::warning("SQL query: " . $sql, ['time' => $query->time]);
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
        ]);

        Passport::useAccessTokenEntity(CustomAccessToken::class);
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        TeamHasDataAccessApplication::observe(TeamHasDataAccessApplicationObserver::class);

        $url = 'https://raw.githubusercontent.com/HDRUK/gateway-web/feat/GAT-6927/public/test.json';

        $featureFlags = Cache::remember('feature_flags', now()->addMinutes(60), function () use ($url) {
            $res = Http::get($url);
            if ($res->successful()) {
                return $res->json();
            }

            logger()->error('Failed to fetch feature flags from GitHub', ['url' => $url]);
            return [];
        });

        if (is_array($featureFlags)) {
            $this->defineFeatureFlags($featureFlags);
        }
    }
}
