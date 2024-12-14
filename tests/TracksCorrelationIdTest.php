<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId\Tests;

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\CorrelationId\Concerns\TracksCorrelationId;
use PhilipRehberger\CorrelationId\CorrelationId;
use PhilipRehberger\CorrelationId\CorrelationIdServiceProvider;
use PhilipRehberger\CorrelationId\Middleware\CorrelationIdJobMiddleware;

class TracksCorrelationIdTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CorrelationIdServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('correlation-id.sentry', false);
    }

    public function test_trait_captures_current_correlation_id(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('trait-capture-id');

        $job = new class
        {
            use TracksCorrelationId;

            public function __construct()
            {
                $this->initializeTracksCorrelationId();
            }
        };

        $this->assertSame('trait-capture-id', $job->correlationId);
    }

    public function test_trait_captures_null_when_no_correlation_id(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $job = new class
        {
            use TracksCorrelationId;

            public function __construct()
            {
                $this->initializeTracksCorrelationId();
            }
        };

        $this->assertNull($job->correlationId);
    }

    public function test_job_middleware_sets_correlation_id_from_job_property(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $job = new \stdClass;
        $job->correlationId = 'job-middleware-id';

        $called = false;
        $middleware = new CorrelationIdJobMiddleware;
        $middleware->handle($job, function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
        $this->assertSame('job-middleware-id', CorrelationId::get());
    }

    public function test_job_middleware_skips_when_no_correlation_id_property(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('original-id');

        $job = new \stdClass;

        $middleware = new CorrelationIdJobMiddleware;
        $middleware->handle($job, function () {});

        $this->assertSame('original-id', CorrelationId::get());
    }

    public function test_job_middleware_skips_when_correlation_id_is_null(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        CorrelationId::set('original-id');

        $job = new \stdClass;
        $job->correlationId = null;

        $middleware = new CorrelationIdJobMiddleware;
        $middleware->handle($job, function () {});

        $this->assertSame('original-id', CorrelationId::get());
    }
}
