<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Laratusk\SharedJobs\DTOs\SharedJobPayload;

it('creates a payload with all properties', function (): void {
    $now = CarbonImmutable::now();

    $payload = new SharedJobPayload(
        name: 'refund',
        payload: ['account_id' => 5],
        jobId: 'test-uuid',
        dispatchedAt: $now,
    );

    expect($payload->name)->toBe('refund')
        ->and($payload->payload)->toBe(['account_id' => 5])
        ->and($payload->jobId)->toBe('test-uuid')
        ->and($payload->dispatchedAt)->toBe($now);
});

it('converts to array', function (): void {
    $now = CarbonImmutable::parse('2024-01-15 10:30:00');

    $payload = new SharedJobPayload(
        name: 'refund',
        payload: ['account_id' => 5],
        jobId: 'test-uuid',
        dispatchedAt: $now,
    );

    $array = $payload->toArray();

    expect($array)->toHaveKeys(['name', 'payload', 'jobId', 'dispatchedAt'])
        ->and($array['name'])->toBe('refund')
        ->and($array['payload'])->toBe(['account_id' => 5])
        ->and($array['jobId'])->toBe('test-uuid');
});

it('is readonly', function (): void {
    $ref = new ReflectionClass(SharedJobPayload::class);

    expect($ref->isReadOnly())->toBeTrue();
});
