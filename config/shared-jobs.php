<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Role
    |--------------------------------------------------------------------------
    |
    | Determines how this app participates in the shared jobs system.
    | 'dispatcher' — can only dispatch jobs (e.g. Admin app)
    | 'consumer'   — can only consume/process jobs (e.g. API app)
    | 'both'       — can dispatch and consume (single app or testing)
    |
    */
    'role' => env('SHARED_JOBS_ROLE', 'both'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | The queue connection name registered in config/queue.php at runtime.
    | Uses Laravel's native database queue driver — no custom driver.
    |
    */
    'connection' => env('SHARED_JOBS_CONNECTION', 'shared-jobs'),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The queue name for shared jobs.
    |
    */
    'queue' => env('SHARED_JOBS_QUEUE', 'shared'),

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | The database table used as the queue backend.
    | This is Laravel's standard jobs table structure.
    |
    */
    'table' => env('SHARED_JOBS_TABLE', 'shared_jobs'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection for the shared jobs table.
    | null = use default database connection.
    | Useful if both apps connect to a shared DB via a named connection.
    |
    */
    'database_connection' => env('SHARED_JOBS_DB_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Wait Timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Default timeout for dispatchAndWait() in seconds.
    |
    */
    'wait_timeout' => (int) env('SHARED_JOBS_WAIT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Wait Poll Interval (milliseconds)
    |--------------------------------------------------------------------------
    |
    | How often to check for results when using dispatchAndWait().
    |
    */
    'wait_poll_interval' => (int) env('SHARED_JOBS_WAIT_POLL_INTERVAL', 500),

    /*
    |--------------------------------------------------------------------------
    | Job Tries
    |--------------------------------------------------------------------------
    |
    | Number of times to attempt a shared job before marking as failed.
    |
    */
    'tries' => (int) env('SHARED_JOBS_TRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry After (seconds)
    |--------------------------------------------------------------------------
    |
    | Seconds before a reserved job is released back to the queue.
    |
    */
    'retry_after' => (int) env('SHARED_JOBS_RETRY_AFTER', 90),

    /*
    |--------------------------------------------------------------------------
    | Backoff (seconds)
    |--------------------------------------------------------------------------
    |
    | Seconds to wait before retrying a failed job.
    |
    */
    'backoff' => (int) env('SHARED_JOBS_BACKOFF', 0),
];
