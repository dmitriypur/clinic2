<?php

namespace App\Providers;

use App\Models\CuratorMedia;
use App\Policies\CuratorMediaPolicy;
use App\Policies\NavigationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use RyanChandler\FilamentNavigation\Models\Navigation;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Navigation::class => NavigationPolicy::class,
        CuratorMedia::class => CuratorMediaPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
