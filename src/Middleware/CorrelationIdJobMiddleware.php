<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId\Middleware;

use Closure;
use PhilipRehberger\CorrelationId\CorrelationId;

/**
 * Queue job middleware that restores the correlation ID from the dispatching context.
 *
 * When a job uses the TracksCorrelationId trait, this middleware sets the
 * correlation ID on the current request before the job executes, ensuring
 * that any logging or downstream calls within the job share the same ID.
 */
class CorrelationIdJobMiddleware
{
    /**
     * Handle the queued job.
     *
     * @param  mixed  $job  The queued job instance.
     * @param  Closure  $next  The next middleware in the pipeline.
     */
    public function handle(mixed $job, Closure $next): void
    {
        if (property_exists($job, 'correlationId') && is_string($job->correlationId) && $job->correlationId !== '') {
            CorrelationId::set($job->correlationId);
        }

        $next($job);
    }
}
