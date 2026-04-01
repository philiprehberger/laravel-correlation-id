<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId\Concerns;

use PhilipRehberger\CorrelationId\CorrelationId;

/**
 * Trait for queue jobs that need to propagate the current correlation ID.
 *
 * When a job is dispatched, the trait captures the active correlation ID
 * so it can be restored when the job is processed by a worker.
 */
trait TracksCorrelationId
{
    /**
     * The correlation ID captured at dispatch time.
     */
    public ?string $correlationId = null;

    /**
     * Boot the trait by capturing the current correlation ID.
     *
     * Uses Laravel's trait initializer convention so it works automatically
     * when the trait is used on any class (including queued jobs).
     */
    public function initializeTracksCorrelationId(): void
    {
        $this->correlationId ??= CorrelationId::get();
    }
}
