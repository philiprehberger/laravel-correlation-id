<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId\Middleware;

use Closure;
use GuzzleHttp\Psr7\Request;
use PhilipRehberger\CorrelationId\CorrelationId;
use Psr\Http\Message\RequestInterface;

/**
 * HTTP client middleware that propagates the correlation ID to outgoing requests.
 *
 * Returns a closure compatible with Laravel's HTTP client middleware stack
 * (Guzzle handler pipeline). The closure adds the correlation ID header
 * to every outgoing HTTP request.
 */
class PropagateCorrelationId
{
    /**
     * Create a Guzzle middleware handler that adds the correlation ID header.
     *
     * @param  string|null  $headerName  The header name to use. Defaults to 'X-Correlation-ID'.
     * @return Closure A Guzzle-compatible middleware closure.
     */
    public static function handler(?string $headerName = null): Closure
    {
        $header = $headerName ?? 'X-Correlation-ID';

        return static function (callable $handler) use ($header): Closure {
            return static function (RequestInterface $request, array $options) use ($handler, $header) {
                $correlationId = CorrelationId::get();

                if ($correlationId !== null) {
                    $request = $request->withHeader($header, $correlationId);
                }

                return $handler($request, $options);
            };
        };
    }
}
