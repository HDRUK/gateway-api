<?php

namespace App\Providers;

use App\AliasReplyScanner\AliasReplyScanner;
use Illuminate\Support\ServiceProvider;

class AliasReplyScannerProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind('aliasreplyscanner', function () {
            return new AliasReplyScanner();
        });
    }
}
