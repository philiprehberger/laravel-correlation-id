<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PhilipRehberger\CorrelationId\Middleware\PropagateCorrelationId;

class CorrelationId
{
    /**
     * Completed trace spans collected during the current lifecycle.
     *
     * @var Span[]
     */
    protected static array $spans = [];

    /**
     * Get the current correlation ID from the active request's attributes.
     *
     * Returns null when no request is available or no correlation ID has
     * been set yet (e.g., before the middleware has run).
     */
    public static function get(): ?string
    {
        try {
            /** @var Request $request */
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
            /** @var Request $request */
            $request = App::make('request');
            $request->attributes->set('correlation_id', $id);
        } catch (\Throwable) {
            // No request available; silently do nothing.
        }
    }

    /**
     * Create a Guzzle-compatible HTTP client middleware that propagates the correlation ID.
     *
     * Use this with Laravel's HTTP client:
     *
     *     Http::withMiddleware(CorrelationId::httpMiddleware())->get($url);
     *
     * @param  string|null  $headerName  The header name to use. Defaults to 'X-Correlation-ID'.
     * @return Closure A Guzzle-compatible middleware closure.
     */
    public static function httpMiddleware(?string $headerName = null): Closure
    {
        return PropagateCorrelationId::handler($headerName);
    }

    /**
     * Start a new trace span with the given name.
     *
     * The span captures the current correlation ID as its parent.
     *
     * @param  string  $name  A descriptive name for the span.
     * @param  array<string, mixed>  $metadata  Arbitrary metadata to attach.
     */
    public static function startSpan(string $name, array $metadata = []): Span
    {
        return new Span(
            name: $name,
            startTime: microtime(true),
            parentId: static::get(),
            metadata: $metadata,
        );
    }

    /**
     * End a span and store it in the completed spans list.
     *
     * Returns the ended span (a new immutable instance with the end time set).
     */
    public static function endSpan(Span $span): Span
    {
        $ended = $span->end();
        static::$spans[] = $ended;

        return $ended;
    }

    /**
     * Get all completed (ended) trace spans.
     *
     * @return Span[]
     */
    public static function spans(): array
    {
        return static::$spans;
    }

    /**
     * Clear all stored trace spans.
     */
    public static function clearSpans(): void
    {
        static::$spans = [];
    }
}
