<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laratusk\SharedJobs\Exceptions\SharedJobException;
use Laratusk\SharedJobs\Exceptions\SharedJobTimeoutException;
use Laratusk\SharedJobs\Jobs\ProcessSharedJob;
use Laratusk\SharedJobs\Services\SharedJobDispatcher;

it('inserts pending row and dispatches job', function (): void {
    Queue::fake();
    config()->set('shared-jobs.wait_timeout', 1);
    config()->set('shared-jobs.wait_poll_interval', 50);

    $dispatcher = new SharedJobDispatcher;

    try {
        $dispatcher->dispatchAndWait('refund', ['account_id' => 5], timeout: 1);
    } catch (SharedJobTimeoutException) {
        // expected
    }

    Queue::assertPushed(ProcessSharedJob::class, function (ProcessSharedJob $job): bool {
        return $job->name === 'refund' && $job->payload === ['account_id' => 5];
    });

    $row = DB::table('shared_job_results')->first();
    expect($row)->not->toBeNull()
        ->and($row->status)->toBe('pending');
});

it('returns result when job is completed', function (): void {
    Queue::fake();
    config()->set('shared-jobs.wait_poll_interval', 10);
    config()->set('shared-jobs.wait_timeout', 5);

    // Use DB::listen to catch the INSERT into shared_job_results,
    // then immediately update the row to 'completed' so the poll finds it
    $updated = false;
    DB::listen(function ($query) use (&$updated): void {
        if (! $updated && str_contains($query->sql, 'shared_job_results') && str_contains($query->sql, 'insert')) {
            $updated = true;
            // Update all pending rows to completed (there should only be one)
            DB::table('shared_job_results')
                ->where('status', 'pending')
                ->update([
                    'result' => json_encode(['success' => true, 'refund_id' => 123]),
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);
        }
    });

    $dispatcher = new SharedJobDispatcher;
    $result = $dispatcher->dispatchAndWait('refund', ['account_id' => 5]);

    expect($result)->toBe(['success' => true, 'refund_id' => 123]);
});

it('throws timeout exception when job does not complete in time', function (): void {
    Queue::fake();
    config()->set('shared-jobs.wait_timeout', 1);
    config()->set('shared-jobs.wait_poll_interval', 100);

    $dispatcher = new SharedJobDispatcher;
    $dispatcher->dispatchAndWait('slow-job', ['data' => 'test'], timeout: 1);
})->throws(SharedJobTimeoutException::class, 'timed out after 1 seconds');

it('throws exception when consumer tries to dispatchAndWait', function (): void {
    Queue::fake();
    config()->set('shared-jobs.role', 'consumer');

    $dispatcher = new SharedJobDispatcher;
    $dispatcher->dispatchAndWait('failing-job', ['data' => 'test']);
})->throws(SharedJobException::class, 'cannot dispatch');

it('throws exception when job status is failed', function (): void {
    Queue::fake();
    config()->set('shared-jobs.wait_poll_interval', 10);
    config()->set('shared-jobs.wait_timeout', 5);

    $updated = false;
    DB::listen(function ($query) use (&$updated): void {
        if (! $updated && str_contains($query->sql, 'shared_job_results') && str_contains($query->sql, 'insert')) {
            $updated = true;
            DB::table('shared_job_results')
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'error' => 'Something went wrong',
                    'updated_at' => now(),
                ]);
        }
    });

    $dispatcher = new SharedJobDispatcher;
    $dispatcher->dispatchAndWait('failing-job', ['data' => 'test']);
})->throws(SharedJobException::class, "Shared job 'failing-job' failed: Something went wrong");
