# Shared Jobs for Laravel

Cross-application job dispatch via a shared database for Laravel. Two separate Laravel apps that connect to the same database can communicate through Laravel's native `database` queue driver and event system.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- A shared database accessible by both applications

> This package uses Laravel's `database` queue driver. Both applications must connect to the same database so they can read/write from the shared jobs table.

## Installation

```bash
composer require laratusk/shared-jobs
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=shared-jobs-migrations
php artisan migrate
```

Optionally, publish the config file to customize defaults:

```bash
php artisan vendor:publish --tag=shared-jobs-config
```

## Configuration

The package works out of the box with sensible defaults. You only need to set environment variables if you want to customize the behavior:

| Variable | Default | Description |
|---|---|---|
| `SHARED_JOBS_ROLE` | `both` | `dispatcher`, `consumer`, or `both` |
| `SHARED_JOBS_CONNECTION` | `shared-jobs` | Queue connection name |
| `SHARED_JOBS_QUEUE` | `shared` | Queue name |
| `SHARED_JOBS_TABLE` | `shared_jobs` | Jobs table name |
| `SHARED_JOBS_DB_CONNECTION` | `null` | Database connection (default connection) |
| `SHARED_JOBS_TRIES` | `3` | Max job attempts |
| `SHARED_JOBS_RETRY_AFTER` | `90` | Retry after seconds |
| `SHARED_JOBS_BACKOFF` | `0` | Backoff seconds |
| `SHARED_JOBS_WAIT_TIMEOUT` | `30` | dispatchAndWait timeout |
| `SHARED_JOBS_WAIT_POLL_INTERVAL` | `500` | Poll interval in ms |

## Usage

### Dispatching Jobs (App 1)

```php
use Laratusk\SharedJobs\Facades\SharedJob;

SharedJob::dispatch('refund', ['account_id' => 5]);
```

### Consuming Jobs (App 2)

Start the worker (should be managed by [Supervisor](https://laravel.com/docs/queues#supervisor-configuration) in production):

```bash
php artisan queue:work shared-jobs --queue=shared
```

Create a listener that extends the base `SharedJobListener`. Laravel's auto-discovery will register it automatically:

```php
use Laratusk\SharedJobs\Events\SharedJobReceived;
use Laratusk\SharedJobs\Listeners\SharedJobListener;

class HandleRefund extends SharedJobListener
{
    protected string $jobName = 'refund';

    public function process(array $payload, SharedJobReceived $event): void
    {
        // Handle the refund...
    }
}
```

Alternatively, you can listen for `SharedJobReceived` directly with a plain listener:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Attribute\AsListener;
use Laratusk\SharedJobs\Events\SharedJobReceived;

#[AsListener(SharedJobReceived::class)]
class HandleRefund
{
    public function handle(SharedJobReceived $event): void
    {
        if ($event->name !== 'refund') {
            return;
        }

        // Handle the refund using $event->payload ...
    }
}
```

### Dispatch and Wait

For synchronous-like communication:

```php
$result = SharedJob::dispatchAndWait('refund', ['account_id' => 5], timeout: 30);
```

In the consumer, respond with data:

```php
public function process(array $payload, SharedJobReceived $event): void
{
    // Process...
    $event->respond(['success' => true, 'refund_id' => 123]);
}
```

### Testing

```php
use Laratusk\SharedJobs\Facades\SharedJob;

SharedJob::fake();

// ... code that dispatches ...

SharedJob::assertDispatched('refund', function (string $name, array $payload): bool {
    return $payload['account_id'] === 5;
});

SharedJob::assertNotDispatched('suspend-account');
SharedJob::assertNothingDispatched();
SharedJob::assertDispatchedTimes('refund', 2);
```

## How It Works

```
App 1                        Shared DB                   App 2
─────                        ─────────                   ─────
SharedJob::dispatch(     →   shared_jobs table      →    php artisan queue:work
  'refund',                  (Laravel's own               shared-jobs --queue=shared
  ['account_id' => 5]       jobs table structure)
)                                                         ↓
                                                   ProcessSharedJob::handle()
                                                         ↓
                                                   event(new SharedJobReceived(...))
                                                         ↓
                                                   Your Listener handles it
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
