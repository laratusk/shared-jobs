<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laratusk\SharedJobs\Contracts\SharedJobResponderInterface;
use Laratusk\SharedJobs\Events\SharedJobReceived;

it('creates event with all properties', function (): void {
    $now = CarbonImmutable::now();

    $event = new SharedJobReceived(
        name: 'refund',
        payload: ['account_id' => 5],
        jobId: 'test-uuid',
        dispatchedAt: $now,
    );

    expect($event->name)->toBe('refund')
        ->and($event->payload)->toBe(['account_id' => 5])
        ->and($event->jobId)->toBe('test-uuid')
        ->and($event->dispatchedAt)->toBe($now);
});

it('is readonly', function (): void {
    $ref = new ReflectionClass(SharedJobReceived::class);

    expect($ref->isReadOnly())->toBeTrue();
});

it('delegates respond to SharedJobResponderInterface', function (): void {
    $jobId = 'response-test-uuid';

    DB::table('shared_job_results')->insert([
        'job_id' => $jobId,
        'result' => null,
        'status' => 'pending',
        'error' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $event = new SharedJobReceived(
        name: 'refund',
        payload: ['account_id' => 5],
        jobId: $jobId,
        dispatchedAt: CarbonImmutable::now(),
    );

    $event->respond(['success' => true, 'amount' => 100]);

    $row = DB::table('shared_job_results')
        ->where('job_id', $jobId)
        ->first();

    expect($row->status)->toBe('completed')
        ->and(json_decode((string) $row->result, true))->toBe(['success' => true, 'amount' => 100]);
});

it('resolves responder from container', function (): void {
    $called = false;
    $capturedJobId = null;
    $capturedData = null;

    $this->app->bind(SharedJobResponderInterface::class, function () use (&$called, &$capturedJobId, &$capturedData): SharedJobResponderInterface {
        return new class($called, $capturedJobId, $capturedData) implements SharedJobResponderInterface
        {
            public function __construct(
                private bool &$called,
                private ?string &$capturedJobId,
                private ?array &$capturedData,
            ) {}

            public function respond(string $jobId, array $data): void
            {
                $this->called = true;
                $this->capturedJobId = $jobId;
                $this->capturedData = $data;
            }
        };
    });

    $event = new SharedJobReceived(
        name: 'refund',
        payload: ['account_id' => 5],
        jobId: 'mock-uuid',
        dispatchedAt: CarbonImmutable::now(),
    );

    $event->respond(['refund_id' => 42]);

    expect($called)->toBeTrue()
        ->and($capturedJobId)->toBe('mock-uuid')
        ->and($capturedData)->toBe(['refund_id' => 42]);
});
