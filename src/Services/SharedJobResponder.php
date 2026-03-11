<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Services;

use Illuminate\Support\Facades\DB;
use Laratusk\SharedJobs\Contracts\SharedJobResponderInterface;

final class SharedJobResponder implements SharedJobResponderInterface
{
    public function respond(string $jobId, array $data): void
    {
        /** @var string|null $dbConnection */
        $dbConnection = config('shared-jobs.database_connection');

        DB::connection($dbConnection)
            ->table('shared_job_results')
            ->where('job_id', $jobId)
            ->update([
                'result' => json_encode($data, JSON_THROW_ON_ERROR),
                'status' => 'completed',
                'updated_at' => now(),
            ]);
    }
}
