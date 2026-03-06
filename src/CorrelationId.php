<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId;

use Illuminate\Support\Facades\App;

class CorrelationId
{
    /**
     * Get the current correlation ID from the active request's attributes.
     *
     * Returns null when no request is available or no correlation ID has
     * been set yet (e.g., before the middleware has run).
     */
    public static function get(): ?string
    {
        try {
            /** @var \Illuminate\Http\Request $request */
            $request = App::make('request');

            $value = $request->attributes->get('correlation_id');

            return is_string($value) && $value !== '' ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Set a correlation ID on the current request's attributes.
     *
     * This is useful in tests or when you need to override the correlation
     * ID set by the middleware. Does nothing if no request is available.
     */
    public static function set(string $id): void
    {
        try {
            /** @var \Illuminate\Http\Request $request */
            $request = App::make('request');
            $request->attributes->set('correlation_id', $id);
        } catch (\Throwable) {
            // No request available; silently do nothing.
        }
    }
}
