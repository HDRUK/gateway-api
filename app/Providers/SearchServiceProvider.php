<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SearchAggregator;
use App\SearchProviders\HDRUK;
use App\SearchProviders\ARDC;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchAggregator::class, function () {
            return new SearchAggregator([
                new HDRUK(),
                new ARDC(),
            ]);
        });
    }
}