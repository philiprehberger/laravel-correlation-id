<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId;

use Illuminate\Support\ServiceProvider;

class CorrelationIdServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/correlation-id.php' => config_path('correlation-id.php'),
            ], 'correlation-id-config');
        }
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/correlation-id.php',
            'correlation-id'
        );
    }
}
