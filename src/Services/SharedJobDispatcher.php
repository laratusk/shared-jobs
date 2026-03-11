<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laratusk\SharedJobs\Contracts\SharedJobDispatcherInterface;
use Laratusk\SharedJobs\Enums\Role;
use Laratusk\SharedJobs\Exceptions\SharedJobException;
use Laratusk\SharedJobs\Exceptions\SharedJobTimeoutException;
use Laratusk\SharedJobs\Jobs\ProcessSharedJob;

final class SharedJobDispatcher implements SharedJobDispatcherInterface
{
    public function dispatch(string $name, array $payload = []): void
    {
        $this->ensureCanDispatch();

        ProcessSharedJob::dispatch(
            name: $name,
            payload: $payload,
            jobId: Str::uuid()->toString(),
            dispatchedAt: CarbonImmutable::now(),
        );
    }

    public function dispatchAndWait(string $name, array $payload = [], ?int $timeout = null): array
    {
        $this->ensureCanDispatch();

        $jobId = Str::uuid()->toString();

        /** @var string|null $dbConnection */
        $dbConnection = config('shared-jobs.database_connection');

        /** @var int $configTimeout */
        $configTimeout = config('shared-jobs.wait_timeout', 30);
        $waitTimeout = $timeout ?? $configTimeout;

        /** @var int $pollInterval */
        $pollInterval = config('shared-jobs.wait_poll_interval', 500);

        DB::connection($dbConnection)
            ->table('shared_job_results')
            ->insert([
                'job_id' => $jobId,
                'result' => null,
                'status' => 'pending',
                'error' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        ProcessSharedJob::dispatch(
            name: $name,
            payload: $payload,
            jobId: $jobId,
            dispatchedAt: CarbonImmutable::now(),
        );

        $startTime = time();

        while (true) {
            $row = DB::connection($dbConnection)
                ->table('shared_job_results')
                ->where('job_id', $jobId)
                ->first();

            if ($row !== null && $row->status === 'completed') {
                /** @var string $result */
                $result = $row->result ?? '[]';

                /** @var array<string, mixed> $decoded */
                $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

                return $decoded;
            }

            if ($row !== null && $row->status === 'failed') {
                throw new SharedJobException(
                    "Shared job '{$name}' failed: " . ($row->error ?? 'Unknown error')
                );
            }

            if ((time() - $startTime) >= $waitTimeout) {
                throw new SharedJobTimeoutException(
                    "Shared job '{$name}' timed out after {$waitTimeout} seconds."
                );
            }

            usleep($pollInterval * 1000);
        }
    }

    private function ensureCanDispatch(): void
    {
        /** @var string $roleValue */
        $roleValue = config('shared-jobs.role', 'both');
        $role = Role::from($roleValue);

        if (! $role->canDispatch()) {
            throw new SharedJobException(
                "This application is configured as a '{$roleValue}' and cannot dispatch shared jobs."
            );
        }
    }
}
