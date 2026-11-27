<?php

namespace App\Providers;

use App\Modules\Contracts\EnrichmentStore;
use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use App\Modules\Stores\EloquentEnrichmentStore;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Read-only once built, so safe to share across Octane requests.
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry(config('modules.modules', [])));

        // Per request under Octane. Never make this a singleton.
        $this->app->scoped(ModuleManager::class);

        $this->app->singleton(EnrichmentStore::class, EloquentEnrichmentStore::class);
    }
}
