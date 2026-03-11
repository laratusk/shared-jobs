<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Laratusk\SharedJobs\Exceptions\SharedJobException;
use Laratusk\SharedJobs\Jobs\ProcessSharedJob;
use Laratusk\SharedJobs\Services\SharedJobDispatcher;

it('dispatches a job to the shared queue', function (): void {
    Queue::fake();

    $dispatcher = new SharedJobDispatcher;
    $dispatcher->dispatch('refund', ['account_id' => 5]);

    Queue::assertPushed(ProcessSharedJob::class, function (ProcessSharedJob $job): bool {
        return $job->name === 'refund'
            && $job->payload === ['account_id' => 5];
    });
});

it('throws when consumer tries to dispatch', function (): void {
    config()->set('shared-jobs.role', 'consumer');

    $dispatcher = new SharedJobDispatcher;
    $dispatcher->dispatch('refund', ['account_id' => 5]);
})->throws(SharedJobException::class, 'cannot dispatch');

it('allows dispatcher role to dispatch', function (): void {
    Queue::fake();
    config()->set('shared-jobs.role', 'dispatcher');

    $dispatcher = new SharedJobDispatcher;
    $dispatcher->dispatch('refund', ['account_id' => 5]);

    Queue::assertPushed(ProcessSharedJob::class);
});

it('allows both role to dispatch', function (): void {
    Queue::fake();
    config()->set('shared-jobs.role', 'both');

    $dispatcher = new SharedJobDispatcher;
    $dispatcher->dispatch('refund', ['account_id' => 5]);

    Queue::assertPushed(ProcessSharedJob::class);
});
