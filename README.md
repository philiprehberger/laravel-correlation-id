# Laravel Correlation ID

[![Tests](https://github.com/philiprehberger/laravel-correlation-id/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-correlation-id/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/laravel-correlation-id.svg)](https://packagist.org/packages/philiprehberger/laravel-correlation-id)
[![License](https://img.shields.io/github/license/philiprehberger/laravel-correlation-id)](LICENSE)

Laravel middleware that generates or propagates correlation IDs for request tracing with automatic log context injection.

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

## Usage

### Register the Middleware

Add the middleware to your HTTP kernel in `bootstrap/app.php`:

```php
use PhilipRehberger\CorrelationId\AddCorrelationId;

->withMiddleware(function (Middleware $middleware) {
    $middleware->prepend(AddCorrelationId::class);
})
```

### Accessing the Correlation ID

```php
// From the request object
$correlationId = $request->attributes->get('correlation_id');

// Via the helper class
use PhilipRehberger\CorrelationId\CorrelationId;

$id = CorrelationId::get();
CorrelationId::set('my-custom-id');
```

### Configuration

```php
// config/correlation-id.php
return [
    'request_headers' => ['X-Request-Id', 'X-Correlation-ID'],
    'response_header' => 'X-Request-Id',
    'log_context_key' => 'correlation_id',
    'sentry'          => true,
];
```

### How It Works

1. The middleware inspects incoming request headers in the order defined by `request_headers`.
2. The first non-empty value found is used as-is (propagation path).
3. When no matching header is present, a new UUID v4 is generated.
4. The ID is stored as a request attribute and shared with the log context.
5. After the handler returns, the ID is written to the response header defined by `response_header`.

### Sentry Integration

When `sentry/sentry-laravel` is installed and `'sentry' => true`, the middleware sets `correlation_id` as a tag on every Sentry event captured during the request.

## API

| Class / Method | Description |
|----------------|-------------|
| `AddCorrelationId` middleware | Generates or propagates the correlation ID and injects it into logs and responses |
| `CorrelationId::get()` | Read the current correlation ID (`null` if not yet set) |
| `CorrelationId::set(string $id)` | Override the correlation ID (useful in tests or CLI commands) |
| `$request->attributes->get('correlation_id')` | Read the ID from the current request |
| `X-Request-Id` response header | Outgoing header carrying the correlation ID (configurable) |
| `correlation_id` log context key | Key injected into every `Log::*` call during the request (configurable) |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
