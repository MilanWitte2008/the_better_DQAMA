<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registreer hier eventuele eigen services in de Laravel-container.
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Plaats hier instellingen die bij het opstarten van de applicatie nodig zijn.
        //
    }
}
