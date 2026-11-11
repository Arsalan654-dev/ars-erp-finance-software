<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super admin bypasses all gates
        Gate::before(function ($user) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Wildcard gate: any @can('xxx.yyy') resolves to user->hasPermission('xxx.yyy')
        Gate::before(function ($user, $ability) {
            return $user->hasPermission($ability) ? true : null;
        });
    }
}
