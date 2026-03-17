<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sentry\SentrySdk;
use Symfony\Component\HttpFoundation\Response;

class AddCorrelationId
{
    /**
     * Handle an incoming request.
     *
     * Generates or propagates a correlation ID for request tracing.
     * Accepts configured request headers from upstream proxies/services.
     * Falls back to generating a new UUID v4 when no header is present.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $this->resolveCorrelationId($request);

        $logContextKey = config('correlation-id.log_context_key', 'correlation_id');
        $responseHeader = config('correlation-id.response_header', 'X-Request-Id');

        $request->attributes->set('correlation_id', $correlationId);
        Log::shareContext([$logContextKey => $correlationId]);

        if (config('correlation-id.sentry', true) && class_exists(SentrySdk::class)) {
            \Sentry\configureScope(fn ($scope) => $scope->setTag('correlation_id', $correlationId));
        }

        $response = $next($request);
        $response->headers->set($responseHeader, $correlationId);

        return $response;
    }

    /**
     * Resolve the correlation ID from the incoming request headers.
     *
     * Iterates through configured request header names in order,
     * returning the first non-empty value found. Falls back to a new UUID.
     */
    protected function resolveCorrelationId(Request $request): string
    {
        $headers = config('correlation-id.request_headers', ['X-Request-Id', 'X-Correlation-ID']);

        foreach ($headers as $header) {
            $value = $request->header($header);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return (string) Str::uuid();
    }
}
