<?php

namespace App\Providers;

use App\Models\Incident;
use App\Policies\IncidentPolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Incident::class => IncidentPolicy::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
