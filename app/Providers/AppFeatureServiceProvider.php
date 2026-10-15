<?php

namespace App\Providers;

use Laravel\Pennant\Feature;
use Illuminate\Support\ServiceProvider;

class AppFeatureServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Feature::resolveScopeUsing(fn ($driver) => null);

        $this->defineSocialLoginProviderFlags();
    }

    /**
     * Per-provider sign-in kill switches, so a provider that is failing -
     * ours or theirs - can be taken out without a deploy.
     *
     * These are defined with a default of `true` as well as being seeded,
     * because an unseeded environment resolving an unknown feature gets
     * `false` from Pennant, and locking everyone out of the Gateway is a
     * far worse failure than leaving a provider enabled a moment longer
     * than intended. The stored value always wins once it exists.
     *
     * Registry gets its own flag here rather than reusing
     * `SafePeopleRegistrySSOEnabled`: that one is a release gate, seeded
     * off because the integration is not yet generally available, and
     * enforcing it at the API would take Registry sign-in down in every
     * environment that has not switched it on. The two mean different
     * things - "is this released" and "is this working" - so they stay
     * separate flags.
     */
    private function defineSocialLoginProviderFlags(): void
    {
        foreach ([
            'LoginGoogleEnabled',
            'LoginAzureEnabled',
            'LoginLinkedInEnabled',
            'LoginOpenAthensEnabled',
            'LoginRegistryEnabled',
        ] as $flag) {
            Feature::define($flag, fn () => true);
        }
    }
}
