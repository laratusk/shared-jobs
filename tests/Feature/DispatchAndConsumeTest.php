<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laratusk\SharedJobs\Events\SharedJobReceived;
use Laratusk\SharedJobs\Facades\SharedJob;
use Laratusk\SharedJobs\Jobs\ProcessSharedJob;

it('dispatches a job via the facade', function (): void {
    Queue::fake();

    SharedJob::dispatch('refund', ['account_id' => 5]);

    Queue::assertPushed(ProcessSharedJob::class, fn (ProcessSharedJob $job): bool => $job->name === 'refund'
        && $job->payload === ['account_id' => 5]
        && $job->jobId !== ''
        && $job->dispatchedAt instanceof CarbonImmutable);
});

it('fires SharedJobReceived event when job is processed', function (): void {
    Event::fake([SharedJobReceived::class]);

    $now = CarbonImmutable::now();

    $job = new ProcessSharedJob(
        name: 'refund',
        payload: ['account_id' => 5],
        jobId: 'test-uuid',
        dispatchedAt: $now,
    );

    $job->handle();

    Event::assertDispatched(SharedJobReceived::class, fn (SharedJobReceived $event): bool => $event->name === 'refund'
        && $event->payload === ['account_id' => 5]
        && $event->jobId === 'test-uuid'
        && $event->dispatchedAt === $now);
});

it('supports fake for testing assertions', function (): void {
    SharedJob::fake();

    SharedJob::dispatch('refund', ['account_id' => 5]);
    SharedJob::dispatch('suspend-account', ['account_id' => 10]);

    SharedJob::assertDispatched('refund');
    SharedJob::assertDispatched('refund', fn (string $name, array $payload): bool => $payload['account_id'] === 5);
    SharedJob::assertDispatched('suspend-account');
    SharedJob::assertDispatchedTimes('refund', 1);
});

it('supports assertNotDispatched via fake', function (): void {
    SharedJob::fake();

    SharedJob::dispatch('refund', ['account_id' => 5]);

    SharedJob::assertNotDispatched('suspend-account');
});

it('supports assertNothingDispatched via fake', function (): void {
    SharedJob::fake();

    SharedJob::assertNothingDispatched();
});

it('sets correct queue connection and queue name on job', function (): void {
    $job = new ProcessSharedJob(
        name: 'refund',
        payload: ['account_id' => 5],
        jobId: 'test-uuid',
        dispatchedAt: CarbonImmutable::now(),
    );

    expect($job->connection)->toBe('shared-jobs')
        ->and($job->queue)->toBe('shared');
});

it('uses configured tries and backoff', function (): void {
    config()->set('shared-jobs.tries', 5);
    config()->set('shared-jobs.backoff', 10);

    $job = new ProcessSharedJob(
        name: 'refund',
        payload: [],
        jobId: 'test-uuid',
        dispatchedAt: CarbonImmutable::now(),
    );

    expect($job->tries())->toBe(5)
        ->and($job->backoff())->toBe(10);
});
