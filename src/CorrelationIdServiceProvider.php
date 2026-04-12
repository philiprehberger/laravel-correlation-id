<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId;

use Illuminate\Http\Request;
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

        Request::macro('correlationId', function (): ?string {
            /** @var Request $this */
            $value = $this->attributes->get('correlation_id');

            return is_string($value) && $value !== '' ? $value : null;
        });
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
