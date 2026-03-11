<?php

declare(strict_types=1);

it('has default configuration values', function (): void {
    expect(config('shared-jobs.role'))->toBe('both')
        ->and(config('shared-jobs.connection'))->toBe('shared-jobs')
        ->and(config('shared-jobs.queue'))->toBe('shared')
        ->and(config('shared-jobs.table'))->toBe('shared_jobs')
        ->and(config('shared-jobs.database_connection'))->toBeNull()
        ->and(config('shared-jobs.wait_timeout'))->toBe(30)
        ->and(config('shared-jobs.wait_poll_interval'))->toBe(500)
        ->and(config('shared-jobs.tries'))->toBe(3)
        ->and(config('shared-jobs.retry_after'))->toBe(90)
        ->and(config('shared-jobs.backoff'))->toBe(0);
});

it('can override config values', function (): void {
    config()->set('shared-jobs.role', 'dispatcher');
    config()->set('shared-jobs.queue', 'custom-queue');
    config()->set('shared-jobs.tries', 5);

    expect(config('shared-jobs.role'))->toBe('dispatcher')
        ->and(config('shared-jobs.queue'))->toBe('custom-queue')
        ->and(config('shared-jobs.tries'))->toBe(5);
});
