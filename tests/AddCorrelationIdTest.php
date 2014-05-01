<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\CorrelationId\AddCorrelationId;
use PhilipRehberger\CorrelationId\CorrelationIdServiceProvider;

class AddCorrelationIdTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CorrelationIdServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('correlation-id.request_headers', ['X-Request-Id', 'X-Correlation-ID']);
        $app['config']->set('correlation-id.response_header', 'X-Request-Id');
        $app['config']->set('correlation-id.log_context_key', 'correlation_id');
        $app['config']->set('correlation-id.sentry', false);
    }

    private function runMiddleware(Request $request): Response
    {
        $middleware = new AddCorrelationId;
        $response = new Response('OK', 200);

        /** @var Response $result */
        $result = $middleware->handle($request, fn () => $response);

        return $result;
    }

    public function test_generates_uuid_when_no_header_present(): void
    {
        $request = Request::create('/test', 'GET');
        $this->runMiddleware($request);

        $correlationId = $request->attributes->get('correlation_id');

        $this->assertNotNull($correlationId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $correlationId
        );
    }

    public function test_propagates_x_request_id_header(): void
    {
        $id = 'upstream-request-abc123';
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_REQUEST_ID' => $id]);

        $this->runMiddleware($request);

        $this->assertSame($id, $request->attributes->get('correlation_id'));
    }

    public function test_propagates_x_correlation_id_header(): void
    {
        $id = 'upstream-correlation-xyz789';
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_CORRELATION_ID' => $id]);

        $this->runMiddleware($request);

        $this->assertSame($id, $request->attributes->get('correlation_id'));
    }

    public function test_x_request_id_takes_priority_over_x_correlation_id(): void
    {
        $requestId = 'from-x-request-id';
        $correlationId = 'from-x-correlation-id';

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_X_REQUEST_ID' => $requestId,
            'HTTP_X_CORRELATION_ID' => $correlationId,
        ]);

        $this->runMiddleware($request);

        $this->assertSame($requestId, $request->attributes->get('correlation_id'));
    }

    public function test_sets_response_header(): void
    {
        $id = 'response-header-test-id';
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_REQUEST_ID' => $id]);

        $response = $this->runMiddleware($request);

        $this->assertSame($id, $response->headers->get('X-Request-Id'));
    }

    public function test_sets_request_attribute(): void
    {
        $request = Request::create('/test', 'GET');

        $this->runMiddleware($request);

        $this->assertTrue($request->attributes->has('correlation_id'));
        $this->assertIsString($request->attributes->get('correlation_id'));
        $this->assertNotEmpty($request->attributes->get('correlation_id'));
    }

    public function test_response_header_matches_request_attribute(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->runMiddleware($request);

        $attributeValue = $request->attributes->get('correlation_id');
        $headerValue = $response->headers->get('X-Request-Id');

        $this->assertSame($attributeValue, $headerValue);
    }

    public function test_custom_response_header_name(): void
    {
        $this->app['config']->set('correlation-id.response_header', 'X-Trace-Id');

        $id = 'custom-header-test';
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_REQUEST_ID' => $id]);

        $response = $this->runMiddleware($request);

        $this->assertSame($id, $response->headers->get('X-Trace-Id'));
        $this->assertNull($response->headers->get('X-Request-Id'));
    }

    public function test_custom_request_headers_order(): void
    {
        $this->app['config']->set('correlation-id.request_headers', ['X-Correlation-ID', 'X-Request-Id']);

        $requestId = 'from-x-request-id';
        $correlationId = 'from-x-correlation-id';

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_X_REQUEST_ID' => $requestId,
            'HTTP_X_CORRELATION_ID' => $correlationId,
        ]);

        $this->runMiddleware($request);

        // With reversed priority, X-Correlation-ID should win
        $this->assertSame($correlationId, $request->attributes->get('correlation_id'));
    }

    public function test_custom_log_context_key(): void
    {
        $this->app['config']->set('correlation-id.log_context_key', 'request_id');

        Log::spy();

        $id = 'log-context-test';
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_REQUEST_ID' => $id]);

        // Middleware must run without errors with a custom log context key
        $response = $this->runMiddleware($request);

        $this->assertSame($id, $request->attributes->get('correlation_id'));
        $this->assertSame($id, $response->headers->get('X-Request-Id'));
    }

    public function test_generated_id_is_consistent_within_request(): void
    {
        $request = Request::create('/test', 'GET');
        $response = $this->runMiddleware($request);

        $attributeValue = $request->attributes->get('correlation_id');
        $headerValue = $response->headers->get('X-Request-Id');

        // The same ID must appear in both places
        $this->assertSame($attributeValue, $headerValue);
        // And must look like a UUID
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $attributeValue
        );
    }
}
