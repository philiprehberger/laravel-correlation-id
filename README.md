# laravel-correlation-id

[![Tests](https://github.com/philiprehberger/laravel-correlation-id/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-correlation-id/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/laravel-correlation-id.svg)](https://packagist.org/packages/philiprehberger/laravel-correlation-id)
[![PHP Version Require](https://img.shields.io/packagist/php-v/philiprehberger/laravel-correlation-id.svg)](https://packagist.org/packages/philiprehberger/laravel-correlation-id)
[![License](https://img.shields.io/github/license/philiprehberger/laravel-correlation-id)](LICENSE)

Laravel middleware that generates or propagates correlation IDs for request tracing, with automatic log context injection and optional Sentry integration.

## What It Does

Every HTTP request your application handles gets a **correlation ID** — a UUID v4 that is either:

- **Generated** automatically when no upstream ID is present, or
- **Propagated** from an `X-Request-Id` or `X-Correlation-ID` header sent by a gateway, load balancer, or calling service.

The ID is then:

- Stored on the request as `$request->attributes->get('correlation_id')`
- Injected into every log entry for the request via `Log::shareContext`
- Written to the outgoing response as an `X-Request-Id` header
- Attached to the active Sentry scope as a tag (when Sentry is installed)

This makes it straightforward to correlate a browser network tab, a log line, and a Sentry error back to the same request.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require philiprehberger/laravel-correlation-id
```

Laravel's auto-discovery registers the service provider automatically.

### Publish the config (optional)

```bash
php artisan vendor:publish --tag=correlation-id-config
```

This copies `config/correlation-id.php` into your application so you can customise it.

## Register the Middleware

Add the middleware to your HTTP kernel in `bootstrap/app.php`:

```php
use PhilipRehberger\CorrelationId\AddCorrelationId;

->withMiddleware(function (Middleware $middleware) {
    $middleware->prepend(AddCorrelationId::class);
})
```

Prepending ensures the correlation ID is set before any other middleware or handler runs, so all log entries within the request lifecycle carry the ID.

## How It Works

1. The middleware inspects incoming request headers in the order defined by `request_headers` config (default: `X-Request-Id`, then `X-Correlation-ID`).
2. The first non-empty value found is used as-is — this is the "propagation" path for service-to-service tracing.
3. When no matching header is present, a new UUID v4 is generated.
4. The ID is stored as a request attribute and shared with the log context.
5. After the downstream handler returns its response, the ID is written to the response header defined by `response_header` (default: `X-Request-Id`).

## Configuration

`config/correlation-id.php`:

```php
return [
    // Headers to inspect on incoming requests, checked in order.
    'request_headers' => ['X-Request-Id', 'X-Correlation-ID'],

    // Header written to every outgoing response.
    'response_header' => 'X-Request-Id',

    // Key used in Log::shareContext and on the request attribute.
    'log_context_key' => 'correlation_id',

    // Set a tag on the active Sentry scope (requires sentry/sentry-laravel).
    'sentry' => true,
];
```

### Changing header priority

If your infrastructure uses `X-Correlation-ID` as the canonical header, flip the order:

```php
'request_headers' => ['X-Correlation-ID', 'X-Request-Id'],
```

## Accessing the Correlation ID

### From the request object

```php
$correlationId = $request->attributes->get('correlation_id');
```

### Via the helper class

```php
use PhilipRehberger\CorrelationId\CorrelationId;

// Read the current ID (returns null if not yet set)
$id = CorrelationId::get();

// Override the ID (useful in tests or CLI commands)
CorrelationId::set('my-custom-id');
```

### From a controller

```php
public function show(Request $request): JsonResponse
{
    return response()->json([
        'data'           => $this->service->getData(),
        'correlation_id' => $request->attributes->get('correlation_id'),
    ]);
}
```

## Log Context

Every `Log::info`, `Log::error`, etc. call made during the request automatically includes the correlation ID:

```json
{
    "message": "Order processed",
    "context": {},
    "extra": {
        "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
    }
}
```

No additional code is required in your application.

## Sentry Integration

When `sentry/sentry-laravel` is installed and `'sentry' => true` in the config, the middleware runs:

```php
\Sentry\configureScope(fn ($scope) => $scope->setTag('correlation_id', $correlationId));
```

This adds `correlation_id` to every Sentry event captured during the request, making it trivial to jump from a log line to the corresponding Sentry issue.

To disable Sentry integration without removing the package, set `'sentry' => false` in the config.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT License. Copyright (c) 2026 Philip Rehberger. See [LICENSE](LICENSE) for details.


