<?php

namespace App\Providers;

use App\Services\TypesenseService;
use Illuminate\Support\ServiceProvider;

class TypesenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TypesenseService::class, function () {
            return new TypesenseService();
        });
    }

    public function boot(): void
    {
        //
    }
}
