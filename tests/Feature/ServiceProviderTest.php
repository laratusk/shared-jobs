<?php

declare(strict_types=1);

use Laratusk\SharedJobs\Contracts\SharedJobDispatcherInterface;
use Laratusk\SharedJobs\Services\SharedJobDispatcher;

it('registers the shared job dispatcher as singleton', function (): void {
    $dispatcher = app(SharedJobDispatcherInterface::class);

    expect($dispatcher)->toBeInstanceOf(SharedJobDispatcher::class);

    $dispatcher2 = app(SharedJobDispatcherInterface::class);

    expect($dispatcher)->toBe($dispatcher2);
});

it('registers the queue connection dynamically', function (): void {
    $connection = config('queue.connections.shared-jobs');

    expect($connection)->toBeArray()
        ->and($connection['driver'])->toBe('database')
        ->and($connection['table'])->toBe('shared_jobs')
        ->and($connection['queue'])->toBe('shared')
        ->and($connection['after_commit'])->toBeTrue();
});

it('uses custom connection name from config', function (): void {
    config()->set('shared-jobs.connection', 'custom-shared');
    config()->set('queue.connections.custom-shared', [
        'driver' => 'database',
        'table' => 'shared_jobs',
        'queue' => 'shared',
        'retry_after' => 90,
        'after_commit' => true,
    ]);

    $connection = config('queue.connections.custom-shared');

    expect($connection)->toBeArray()
        ->and($connection['driver'])->toBe('database');
});
