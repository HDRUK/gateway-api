<?php

namespace App\Providers;

use App\Auth\JwtUserResolver;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Backs the `jwt` guard declared in config/auth.php. Additive: it
        // gives new and migrated code a real Auth::user()-style accessor
        // without disturbing the jwt_user payload the existing consumers
        // still read out of the request input bag.
        Auth::viaRequest('jwt', function (Request $request): ?\App\Models\User {
            return app(JwtUserResolver::class)->resolve($request);
        });
    }
}
