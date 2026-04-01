<?php

declare(strict_types=1);

namespace PhilipRehberger\CorrelationId;

/**
 * Immutable value object representing a single trace span.
 *
 * Spans track the timing and context of an operation within a correlation.
 * They are created via CorrelationId::startSpan() and ended via CorrelationId::endSpan().
 */
class Span
{
    /**
     * Create a new Span instance.
     *
     * @param  string  $name  The name identifying this span.
     * @param  float  $startTime  The start time as a Unix timestamp with microseconds.
     * @param  string|null  $parentId  The correlation ID under which this span was created.
     * @param  array<string, mixed>  $metadata  Arbitrary metadata attached to the span.
     * @param  float|null  $endTime  The end time as a Unix timestamp with microseconds, or null if still running.
     */
    public function __construct(
        public readonly string $name,
        public readonly float $startTime,
        public readonly ?string $parentId = null,
        public readonly array $metadata = [],
        public readonly ?float $endTime = null,
    ) {}

    /**
     * Return a new Span instance with the end time set to now.
     *
     * The original span is unchanged (immutable).
     */
    public function end(): self
    {
        return new self(
            name: $this->name,
            startTime: $this->startTime,
            parentId: $this->parentId,
            metadata: $this->metadata,
            endTime: microtime(true),
        );
    }

    /**
     * Get the duration of the span in milliseconds.
     *
     * Returns null if the span has not been ended yet.
     */
    public function durationMs(): ?float
    {
        if ($this->endTime === null) {
            return null;
        }

        return ($this->endTime - $this->startTime) * 1000.0;
    }

    /**
     * Convert the span to an array representation.
     *
     * @return array{name: string, start_time: float, end_time: float|null, duration_ms: float|null, parent_id: string|null, metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'duration_ms' => $this->durationMs(),
            'parent_id' => $this->parentId,
            'metadata' => $this->metadata,
        ];
    }
}
