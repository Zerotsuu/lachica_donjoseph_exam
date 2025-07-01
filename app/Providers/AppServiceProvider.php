<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel 12 Performance Optimizations
        if ($this->app->isProduction()) {
            // Disable lazy loading in production to prevent N+1 queries
            Model::preventLazyLoading();
            
            // Prevent silently discarding attributes
            Model::preventSilentlyDiscardingAttributes();
            
            // Prevent accessing missing attributes
            Model::preventAccessingMissingAttributes();
        }

        // Define admin gate for easier authorization checks
        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });
    }
}
