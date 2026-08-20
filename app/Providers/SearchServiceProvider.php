<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Context\PartnerContext;
use App\Services\SearchAggregator;
use App\SearchProviders\HDRUK;
use App\SearchProviders\ARDC;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchAggregator::class, function ($app) {
            return new SearchAggregator([
                new HDRUK($app->make(PartnerContext::class)),
                new ARDC(),
            ]);
        });
    }
}
