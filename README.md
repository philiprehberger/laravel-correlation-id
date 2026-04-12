# Laravel Correlation ID

[![Tests](https://github.com/philiprehberger/laravel-correlation-id/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-correlation-id/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/laravel-correlation-id.svg)](https://packagist.org/packages/philiprehberger/laravel-correlation-id)
[![Last updated](https://img.shields.io/github/last-commit/philiprehberger/laravel-correlation-id)](https://github.com/philiprehberger/laravel-correlation-id/commits/main)

Laravel middleware that generates or propagates correlation IDs for request tracing with automatic log context injection.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require philiprehberger/laravel-correlation-id
```

The service provider is registered automatically via Laravel package auto-discovery.

Optionally publish the config:

```bash
php artisan vendor:publish --tag=correlation-id-config
```

## Usage

```php
use PhilipRehberger\CorrelationId\AddCorrelationId;

// Register the middleware in bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->prepend(AddCorrelationId::class);
})
```

### Accessing the Correlation ID

```php
use PhilipRehberger\CorrelationId\CorrelationId;

// Via the helper class
$id = CorrelationId::get();
CorrelationId::set('my-custom-id');

// Via the request macro
$id = $request->correlationId();
```

### Configuration

```php
// config/correlation-id.php
return [
    'request_headers' => ['X-Request-Id', 'X-Correlation-ID'],
    'response_header' => 'X-Request-Id',
    'log_context_key' => 'correlation_id',
    'sentry'          => true,
    'generator'       => 'uuid',
];
```

### How It Works

1. The middleware inspects incoming request headers in the order defined by `request_headers`.
2. The first non-empty value found is used as-is (propagation path).
3. When no matching header is present, a new ID is generated using the configured generator.
4. The ID is stored as a request attribute and shared with the log context.
5. After the handler returns, the ID is written to the response header defined by `response_header`.

### Custom ID Generator

Control how new correlation IDs are generated when no upstream header is present:

```php
// config/correlation-id.php

// UUID v4 (default)
'generator' => 'uuid',

// UUID v7 (time-ordered, sortable)
'generator' => 'uuid7',

// ULID (compact, sortable)
'generator' => 'ulid',

// Custom callable
'generator' => fn () => 'prefix-' . bin2hex(random_bytes(16)),
```

### Queue Job Propagation

Propagate the correlation ID from the dispatching context into queued jobs:

```php
use PhilipRehberger\CorrelationId\Concerns\TracksCorrelationId;
use PhilipRehberger\CorrelationId\Middleware\CorrelationIdJobMiddleware;

class ProcessOrder implements ShouldQueue
{
    use TracksCorrelationId;

    public function middleware(): array
    {
        return [new CorrelationIdJobMiddleware];
    }

    public function handle(): void
    {
        // CorrelationId::get() returns the same ID from dispatch time
    }
}
```

The `TracksCorrelationId` trait captures the current correlation ID when the job is created. The `CorrelationIdJobMiddleware` restores it when the job runs on a worker.

### HTTP Client Propagation

Automatically forward the correlation ID to outgoing HTTP requests made with Laravel's HTTP client:

```php
use Illuminate\Support\Facades\Http;
use PhilipRehberger\CorrelationId\CorrelationId;

$response = Http::withMiddleware(CorrelationId::httpMiddleware())
    ->get('https://api.example.com/orders');

// Uses a custom header name
$response = Http::withMiddleware(CorrelationId::httpMiddleware('X-Request-Id'))
    ->get('https://api.example.com/orders');
```

The middleware adds the `X-Correlation-ID` header (or your custom header) to every outgoing request.

### Trace Spans

Track the timing of operations within a request using lightweight trace spans:

```php
use PhilipRehberger\CorrelationId\CorrelationId;

$span = CorrelationId::startSpan('external-api-call', ['url' => $url]);

$response = Http::get($url);

$ended = CorrelationId::endSpan($span);

// Access span data
$ended->durationMs();  // Duration in milliseconds
$ended->toArray();     // Full array representation

// Retrieve all completed spans
$spans = CorrelationId::spans();

// Clear spans (e.g., between tests)
CorrelationId::clearSpans();
```

Spans are immutable value objects. Calling `endSpan()` returns a new instance with the end time set and stores it for later retrieval.

### Sentry Integration

When `sentry/sentry-laravel` is installed and `'sentry' => true`, the middleware sets `correlation_id` as a tag on every Sentry event captured during the request.

## API

| Class / Method | Description |
|----------------|-------------|
| `AddCorrelationId` middleware | Generates or propagates the correlation ID and injects it into logs and responses |
| `CorrelationId::get()` | Read the current correlation ID (`null` if not yet set) |
| `CorrelationId::set(string $id)` | Override the correlation ID (useful in tests or CLI commands) |
| `CorrelationId::generate()` | Generate a new correlation ID using the configured generator |
| `CorrelationId::reset()` | Clear the correlation ID and all trace spans for the current request |
| `CorrelationId::httpMiddleware(?string $headerName)` | Returns a Guzzle middleware closure for HTTP client propagation |
| `CorrelationId::startSpan(string $name, array $metadata)` | Start a new trace span linked to the current correlation ID |
| `CorrelationId::endSpan(Span $span)` | End a span and store it for retrieval |
| `CorrelationId::spans()` | Get all completed trace spans |
| `CorrelationId::clearSpans()` | Clear all stored trace spans |
| `$request->correlationId()` | Request macro that returns the current correlation ID |
| `TracksCorrelationId` trait | Captures the correlation ID at dispatch time for queue jobs |
| `CorrelationIdJobMiddleware` | Queue job middleware that restores the correlation ID |
| `PropagateCorrelationId::handler()` | Static factory for the HTTP client propagation middleware |
| `Span` value object | Immutable span with `name`, `durationMs()`, `toArray()` |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## Support

If you find this project useful:

⭐ [Star the repo](https://github.com/philiprehberger/laravel-correlation-id)

🐛 [Report issues](https://github.com/philiprehberger/laravel-correlation-id/issues?q=is%3Aissue+is%3Aopen+label%3Abug)

💡 [Suggest features](https://github.com/philiprehberger/laravel-correlation-id/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement)

❤️ [Sponsor development](https://github.com/sponsors/philiprehberger)

🌐 [All Open Source Projects](https://philiprehberger.com/open-source-packages)

💻 [GitHub Profile](https://github.com/philiprehberger)

🔗 [LinkedIn Profile](https://www.linkedin.com/in/philiprehberger)

## License

[MIT](LICENSE)
