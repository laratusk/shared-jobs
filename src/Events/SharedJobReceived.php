<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Events;

use Carbon\CarbonImmutable;
use Laratusk\SharedJobs\Contracts\SharedJobResponderInterface;

final readonly class SharedJobReceived
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
     * Write a result back to shared_job_results table for dispatchAndWait().
     *
     * @param  array<string, mixed>  $data
     */
    public function respond(array $data): void
    {
        /** @var SharedJobResponderInterface $responder */
        $responder = app(SharedJobResponderInterface::class);

        $responder->respond($this->jobId, $data);
    }
}
