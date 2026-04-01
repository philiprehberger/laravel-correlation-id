<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId\Tests;

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\CorrelationId\CorrelationId;
use PhilipRehberger\CorrelationId\CorrelationIdServiceProvider;
use PhilipRehberger\CorrelationId\Span;

class SpanTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CorrelationIdServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('correlation-id.sentry', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        CorrelationId::clearSpans();
    }

    public function test_start_span_creates_span_with_parent_id(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('span-parent-id');

        $span = CorrelationId::startSpan('test-operation');

        $this->assertSame('test-operation', $span->name);
        $this->assertSame('span-parent-id', $span->parentId);
        $this->assertIsFloat($span->startTime);
        $this->assertNull($span->endTime);
        $this->assertSame([], $span->metadata);
    }

    public function test_start_span_captures_metadata(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $span = CorrelationId::startSpan('db-query', ['table' => 'users', 'query' => 'SELECT *']);

        $this->assertSame(['table' => 'users', 'query' => 'SELECT *'], $span->metadata);
    }

    public function test_end_sets_duration(): void
    {
        $span = new Span(
            name: 'test-span',
            startTime: microtime(true) - 0.1,
            parentId: 'parent-123',
        );

        $this->assertNull($span->durationMs());

        $ended = $span->end();

        $this->assertNotNull($ended->endTime);
        $this->assertNotNull($ended->durationMs());
        $this->assertGreaterThan(0.0, $ended->durationMs());
        // Original is unchanged (immutable)
        $this->assertNull($span->endTime);
    }

    public function test_spans_returns_completed_spans(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('spans-test-id');

        $span1 = CorrelationId::startSpan('operation-1');
        $span2 = CorrelationId::startSpan('operation-2');

        CorrelationId::endSpan($span1);
        CorrelationId::endSpan($span2);

        $spans = CorrelationId::spans();

        $this->assertCount(2, $spans);
        $this->assertSame('operation-1', $spans[0]->name);
        $this->assertSame('operation-2', $spans[1]->name);
        $this->assertNotNull($spans[0]->endTime);
        $this->assertNotNull($spans[1]->endTime);
    }

    public function test_to_array_format(): void
    {
        $span = new Span(
            name: 'array-test',
            startTime: 1000.5,
            parentId: 'parent-abc',
            metadata: ['key' => 'value'],
            endTime: 1001.0,
        );

        $array = $span->toArray();

        $this->assertSame([
            'name' => 'array-test',
            'start_time' => 1000.5,
            'end_time' => 1001.0,
            'duration_ms' => 500.0,
            'parent_id' => 'parent-abc',
            'metadata' => ['key' => 'value'],
        ], $array);
    }

    public function test_to_array_with_unended_span(): void
    {
        $span = new Span(
            name: 'unended',
            startTime: 1000.0,
        );

        $array = $span->toArray();

        $this->assertNull($array['end_time']);
        $this->assertNull($array['duration_ms']);
        $this->assertNull($array['parent_id']);
        $this->assertSame([], $array['metadata']);
    }

    public function test_clear_spans_empties_list(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $span = CorrelationId::startSpan('to-be-cleared');
        CorrelationId::endSpan($span);

        $this->assertCount(1, CorrelationId::spans());

        CorrelationId::clearSpans();

        $this->assertSame([], CorrelationId::spans());
    }

    public function test_end_span_returns_ended_span(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $span = CorrelationId::startSpan('return-test');
        $ended = CorrelationId::endSpan($span);

        $this->assertNotNull($ended->endTime);
        $this->assertSame('return-test', $ended->name);
    }
}
