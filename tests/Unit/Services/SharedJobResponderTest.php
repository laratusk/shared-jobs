<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Laratusk\SharedJobs\Services\SharedJobResponder;

it('updates job result to completed', function (): void {
    $jobId = 'responder-test-uuid';

    DB::table('shared_job_results')->insert([
        'job_id' => $jobId,
        'result' => null,
        'status' => 'pending',
        'error' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $responder = new SharedJobResponder;
    $responder->respond($jobId, ['success' => true, 'amount' => 100]);

    $row = DB::table('shared_job_results')->where('job_id', $jobId)->first();

    expect($row->status)->toBe('completed')
        ->and(json_decode($row->result, true))->toBe(['success' => true, 'amount' => 100]);
});
