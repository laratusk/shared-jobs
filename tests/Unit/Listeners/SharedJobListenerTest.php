<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Laratusk\SharedJobs\Events\SharedJobReceived;
use Laratusk\SharedJobs\Listeners\SharedJobListener;

beforeEach(function (): void {
    $this->processedPayloads = [];
    $test = $this;

    $this->listener = new class($test) extends SharedJobListener
    {
        protected string $jobName = 'refund';

        /** @var \Laratusk\SharedJobs\Tests\TestCase */
        private $test;

        public function __construct($test)
        {
            $this->test = $test;
        }

        public function process(array $payload, SharedJobReceived $event): void
        {
            $this->test->processedPayloads[] = $payload;
        }
    };
});

it('processes event when job name matches', function (): void {
    $event = new SharedJobReceived(
        name: 'refund',
        payload: ['account_id' => 5],
        jobId: 'test-uuid',
        dispatchedAt: CarbonImmutable::now(),
    );

    $this->listener->handle($event);

    expect($this->processedPayloads)->toHaveCount(1)
        ->and($this->processedPayloads[0])->toBe(['account_id' => 5]);
});

it('skips event when job name does not match', function (): void {
    $event = new SharedJobReceived(
        name: 'suspend-account',
        payload: ['account_id' => 5],
        jobId: 'test-uuid',
        dispatchedAt: CarbonImmutable::now(),
    );

    $this->listener->handle($event);

    expect($this->processedPayloads)->toBeEmpty();
});
