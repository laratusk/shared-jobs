<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laratusk\SharedJobs\Events\SharedJobReceived;

final class ProcessSharedJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $name,
        public readonly array $payload,
        public readonly string $jobId,
        public readonly CarbonImmutable $dispatchedAt,
    ) {
        /** @var string $connection */
        $connection = config('shared-jobs.connection', 'shared-jobs');

        /** @var string $queue */
        $queue = config('shared-jobs.queue', 'shared');

        $this->onConnection($connection);
        $this->onQueue($queue);
    }

    public function handle(): void
    {
        event(new SharedJobReceived(
            name: $this->name,
            payload: $this->payload,
            jobId: $this->jobId,
            dispatchedAt: $this->dispatchedAt,
        ));
    }

    public function tries(): int
    {
        /** @var int $tries */
        $tries = config('shared-jobs.tries', 3);

        return $tries;
    }

    public function backoff(): int
    {
        /** @var int $backoff */
        $backoff = config('shared-jobs.backoff', 0);

        return $backoff;
    }
}
