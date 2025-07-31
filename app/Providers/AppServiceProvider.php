<?php

namespace App\Providers;

use Config;
use Laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\SSO\CustomAccessToken;
use App\Models\DataAccessApplication;
use App\Observers\DataAccessApplicationObserver;

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

        // Passport::useAccessTokenEntity(CustomAccessToken::class);
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        DataAccessApplication::observe(DataAccessApplicationObserver::class);
    }

}
