<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId\Tests;

use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\CorrelationId\CorrelationId;
use PhilipRehberger\CorrelationId\CorrelationIdServiceProvider;
use PhilipRehberger\CorrelationId\Middleware\PropagateCorrelationId;

class HttpPropagationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CorrelationIdServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('correlation-id.sentry', false);
    }

    public function test_middleware_adds_header_to_outgoing_request(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('http-propagation-id');

        $middleware = PropagateCorrelationId::handler();

        $capturedRequest = null;
        $innerHandler = function (Psr7Request $request, array $options) use (&$capturedRequest) {
            $capturedRequest = $request;

            return null;
        };

        $handler = $middleware($innerHandler);
        $handler(new Psr7Request('GET', 'https://example.com/api'), []);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('http-propagation-id', $capturedRequest->getHeaderLine('X-Correlation-ID'));
    }

    public function test_middleware_uses_custom_header_name(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('custom-header-id');

        $middleware = PropagateCorrelationId::handler('X-Trace-Id');

        $capturedRequest = null;
        $innerHandler = function (Psr7Request $request, array $options) use (&$capturedRequest) {
            $capturedRequest = $request;

            return null;
        };

        $handler = $middleware($innerHandler);
        $handler(new Psr7Request('GET', 'https://example.com/api'), []);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('custom-header-id', $capturedRequest->getHeaderLine('X-Trace-Id'));
        $this->assertFalse($capturedRequest->hasHeader('X-Correlation-ID'));
    }

    public function test_middleware_does_not_add_header_when_no_correlation_id(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $middleware = PropagateCorrelationId::handler();

        $capturedRequest = null;
        $innerHandler = function (Psr7Request $request, array $options) use (&$capturedRequest) {
            $capturedRequest = $request;

            return null;
        };

        $handler = $middleware($innerHandler);
        $handler(new Psr7Request('GET', 'https://example.com/api'), []);

        $this->assertNotNull($capturedRequest);
        $this->assertFalse($capturedRequest->hasHeader('X-Correlation-ID'));
    }

    public function test_http_middleware_convenience_method(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('convenience-method-id');

        $middleware = CorrelationId::httpMiddleware();

        $capturedRequest = null;
        $innerHandler = function (Psr7Request $request, array $options) use (&$capturedRequest) {
            $capturedRequest = $request;

            return null;
        };

        $handler = $middleware($innerHandler);
        $handler(new Psr7Request('GET', 'https://example.com/api'), []);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('convenience-method-id', $capturedRequest->getHeaderLine('X-Correlation-ID'));
    }

    public function test_http_middleware_convenience_method_with_custom_header(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('custom-convenience-id');

        $middleware = CorrelationId::httpMiddleware('X-Request-Id');

        $capturedRequest = null;
        $innerHandler = function (Psr7Request $request, array $options) use (&$capturedRequest) {
            $capturedRequest = $request;

            return null;
        };

        $handler = $middleware($innerHandler);
        $handler(new Psr7Request('GET', 'https://example.com/api'), []);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('custom-convenience-id', $capturedRequest->getHeaderLine('X-Request-Id'));
    }
}
