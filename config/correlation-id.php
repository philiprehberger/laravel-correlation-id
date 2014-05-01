<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Request Headers
    |--------------------------------------------------------------------------
    |
    | The list of incoming request header names to inspect when looking for
    | an upstream correlation ID. Headers are checked in the order listed
    | and the first non-empty value wins. When none match, a new UUID is
    | generated automatically.
    |
    */

    'request_headers' => ['X-Request-Id', 'X-Correlation-ID'],

    /*
    |--------------------------------------------------------------------------
    | Response Header
    |--------------------------------------------------------------------------
    |
    | The header name written to every outgoing response so that API clients
    | and downstream services can trace the request back to its origin.
    |
    */

    'response_header' => 'X-Request-Id',

    /*
    |--------------------------------------------------------------------------
    | Log Context Key
    |--------------------------------------------------------------------------
    |
    | The key used when injecting the correlation ID into the shared log
    | context via Log::shareContext(). Every log entry made during the
    | request lifecycle will include this key automatically.
    |
    */

    'log_context_key' => 'correlation_id',

    /*
    |--------------------------------------------------------------------------
    | Sentry Integration
    |--------------------------------------------------------------------------
    |
    | When enabled, the correlation ID is attached to the active Sentry scope
    | as a tag. This links Sentry error reports back to the originating
    | request. Only takes effect when the sentry/sentry-laravel package is
    | installed.
    |
    */

    'sentry' => true,

];
