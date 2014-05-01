# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-03-05

### Added

- `AddCorrelationId` middleware that generates a UUID v4 correlation ID or propagates one from an upstream `X-Request-Id` / `X-Correlation-ID` header.
- Automatic injection of the correlation ID into all log entries for the request via `Log::shareContext`.
- Automatic `X-Request-Id` response header so downstream consumers and API clients can reference the same ID.
- Optional Sentry integration: attaches the correlation ID as a tag on the active Sentry scope when `sentry/sentry-laravel` is installed.
- `CorrelationId` helper class with static `get()` and `set()` methods for reading/writing the correlation ID from application code.
- `CorrelationIdServiceProvider` with `mergeConfigFrom` and config publishing support.
- Fully configurable via `config/correlation-id.php`: request header names, response header name, log context key, and Sentry toggle.
- Laravel auto-discovery via `extra.laravel.providers` in `composer.json`.
- PHPUnit 11 test suite covering UUID generation, header propagation, priority resolution, response headers, and the helper class.
