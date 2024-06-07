<?php

namespace App\Providers;

use Config;
use Illuminate\Support\ServiceProvider;

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
                    } else if (is_string($binding)) {
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

        $this->app->singleton(HubspotService::class, function ($app) {
            return new HubspotService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
