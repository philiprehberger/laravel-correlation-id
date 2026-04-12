<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\CorrelationId\AddCorrelationId;
use PhilipRehberger\CorrelationId\CorrelationId;
use PhilipRehberger\CorrelationId\CorrelationIdServiceProvider;

class CorrelationIdTest extends TestCase
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

    public function test_get_returns_null_when_no_request(): void
    {
        // Bind a fresh request with no attributes set
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $this->assertNull(CorrelationId::get());
    }

    public function test_get_returns_correlation_id_after_middleware_runs(): void
    {
        $id = 'helper-get-test-id';
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_REQUEST_ID' => $id]);
        $this->app->instance('request', $request);

        $middleware = new AddCorrelationId;
        $middleware->handle($request, fn () => new Response('OK'));

        $this->assertSame($id, CorrelationId::get());
    }

    public function test_set_writes_to_request_attributes(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('manually-set-id');

        $this->assertSame('manually-set-id', $request->attributes->get('correlation_id'));
    }

    public function test_get_returns_value_written_by_set(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('round-trip-id');

        $this->assertSame('round-trip-id', CorrelationId::get());
    }

    public function test_set_overwrites_existing_value(): void
    {
        $id = 'original-id';
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_REQUEST_ID' => $id]);
        $this->app->instance('request', $request);

        $middleware = new AddCorrelationId;
        $middleware->handle($request, fn () => new Response('OK'));

        $this->assertSame($id, CorrelationId::get());

        CorrelationId::set('overridden-id');

        $this->assertSame('overridden-id', CorrelationId::get());
    }

    public function test_get_returns_null_for_empty_string_attribute(): void
    {
        $request = Request::create('/test', 'GET');
        $request->attributes->set('correlation_id', '');
        $this->app->instance('request', $request);

        $this->assertNull(CorrelationId::get());
    }

    public function test_generate_returns_uuid_by_default(): void
    {
        $id = CorrelationId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id
        );
    }

    public function test_generate_returns_uuid7_when_configured(): void
    {
        $this->app['config']->set('correlation-id.generator', 'uuid7');

        $id = CorrelationId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id
        );
    }

    public function test_generate_returns_ulid_when_configured(): void
    {
        $this->app['config']->set('correlation-id.generator', 'ulid');

        $id = CorrelationId::generate();

        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/i', $id);
    }

    public function test_generate_uses_custom_callable(): void
    {
        $this->app['config']->set('correlation-id.generator', fn () => 'custom-fixed-id');

        $id = CorrelationId::generate();

        $this->assertSame('custom-fixed-id', $id);
    }

    public function test_reset_clears_request_attribute_and_spans(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('will-be-cleared');
        $span = CorrelationId::startSpan('test-span');
        CorrelationId::endSpan($span);

        $this->assertSame('will-be-cleared', CorrelationId::get());
        $this->assertCount(1, CorrelationId::spans());

        CorrelationId::reset();

        $this->assertNull(CorrelationId::get());
        $this->assertSame([], CorrelationId::spans());
    }

    public function test_request_macro_returns_correlation_id(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('macro-test-id');

        $this->assertSame('macro-test-id', $request->correlationId());
    }

    public function test_request_macro_returns_null_when_not_set(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $this->assertNull($request->correlationId());
    }
}
