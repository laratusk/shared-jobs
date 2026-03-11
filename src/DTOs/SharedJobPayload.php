<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\DTOs;

use Carbon\CarbonImmutable;

final readonly class SharedJobPayload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $name,
        public array $payload,
        public string $jobId,
        public CarbonImmutable $dispatchedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'payload' => $this->payload,
            'jobId' => $this->jobId,
            'dispatchedAt' => $this->dispatchedAt->toIso8601String(),
        ];
    }
}
