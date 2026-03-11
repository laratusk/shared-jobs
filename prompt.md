# Task: Build `laratusk/shared-jobs` Laravel Package

## Package Purpose

A Laravel package that enables cross-application job dispatch via a shared database. Two separate Laravel apps (e.g., Admin and API) that connect to the same database can communicate through Laravel's native `database` queue driver and event system. No magic — only Laravel's own queue worker, event dispatcher, and database driver.

## Flow

```
Admin App                    Shared DB                   API App
─────────                    ─────────                   ───────
SharedJob::dispatch(     →   shared_jobs table      →    php artisan queue:work
  'refund',                  (Laravel's own               shared-jobs --queue=shared
  ['account_id' => 5]       jobs table structure)
)                                                         ↓
                                                    ProcessSharedJob::handle()
                                                         ↓
                                                    event(new SharedJobReceived(
                                                      name: 'refund',
                                                      payload: ['account_id' => 5]
                                                    ))
                                                         ↓
                                                    User's Listener handles it
```

## Tech Stack & Standards

- **PHP**: 8.2+ (`declare(strict_types=1)` in every file)
- **Laravel**: 11.x+ / 12.x+ compatibility
- **Testing**: Pest 3.x with full unit + feature test coverage (min 80%)
- **Static Analysis**: PHPStan Level 9 (max) with Larastan
- **Code Quality**: Rector for automated refactoring
- **CI/CD**: GitHub Actions workflow (PHP 8.2/8.3/8.4, Laravel 11/12 matrix)
- **Code Style**: Laravel Pint (PSR-12 based)
- **Every class**: `final` or `readonly`
- **Every method**: explicit return types
- **Every property**: typed
- **Constructor promotion** for all dependencies
- **Enums** over constants
- **`match`** over `switch`
- **Namespace**: `Laratusk\SharedJobs`

## Package Structure

```
laratusk/shared-jobs/
├── src/
│   ├── SharedJobsServiceProvider.php
│   ├── Facades/
│   │   └── SharedJob.php
│   ├── Contracts/
│   │   └── SharedJobDispatcherInterface.php
│   ├── DTOs/
│   │   └── SharedJobPayload.php          # readonly DTO: name, payload, jobId, dispatchedAt
│   ├── Enums/
│   │   └── Role.php                      # Dispatcher, Consumer, Both
│   ├── Events/
│   │   └── SharedJobReceived.php         # Event fired when consumer picks up job
│   ├── Exceptions/
│   │   ├── SharedJobException.php
│   │   └── SharedJobTimeoutException.php
│   ├── Jobs/
│   │   └── ProcessSharedJob.php          # The actual queue job (internal) — fires SharedJobReceived event
│   ├── Listeners/
│   │   └── SharedJobListener.php         # Optional base class with $jobName auto-filter
│   ├── Services/
│   │   └── SharedJobDispatcher.php       # Dispatches jobs to shared queue connection
│   ├── Support/
│   │   └── SharedJobFake.php             # For testing: assertDispatched, assertNotDispatched
│   └── Console/
│       └── Commands/
│           └── SharedJobsTableCommand.php # Migration stub generator (like queue:table)
├── config/
│   └── shared-jobs.php
├── database/
│   └── migrations/
│       └── create_shared_jobs_table.php
├── tests/
│   ├── Unit/
│   │   ├── DTOs/
│   │   │   └── SharedJobPayloadTest.php
│   │   ├── Enums/
│   │   │   └── RoleTest.php
│   │   ├── Events/
│   │   │   └── SharedJobReceivedTest.php
│   │   ├── Services/
│   │   │   └── SharedJobDispatcherTest.php
│   │   └── Support/
│   │       └── SharedJobFakeTest.php
│   ├── Feature/
│   │   ├── DispatchAndConsumeTest.php    # Full flow: dispatch → worker → event fired
│   │   ├── ServiceProviderTest.php
│   │   └── ConfigTest.php
│   ├── Pest.php
│   └── TestCase.php
├── .github/
│   └── workflows/
│       └── ci.yml
├── .gitattributes
├── .gitignore
├── composer.json
├── phpstan.neon
├── rector.php
├── pint.json
├── phpunit.xml
├── CHANGELOG.md
├── LICENSE.md
└── README.md
```

## Config File (`config/shared-jobs.php`)

Everything MUST be configurable via `.env`. No hardcoded values.

```php
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
```

## Core Implementation Details

### 1. ServiceProvider (`SharedJobsServiceProvider.php`)

The provider MUST:

- Merge config
- Register queue connection dynamically into `config/queue.php` connections — using Laravel's native `database` driver with the configured table/connection. This is KEY: no custom driver.
- Bind `SharedJobDispatcherInterface` → `SharedJobDispatcher` as singleton
- Load migrations
- Register commands

```php
// In register(), dynamically add the queue connection:
$connectionName = config('shared-jobs.connection');

$this->app['config']->set("queue.connections.{$connectionName}", [
    'driver' => 'database',
    'connection' => config('shared-jobs.database_connection'),
    'table' => config('shared-jobs.table'),
    'queue' => config('shared-jobs.queue'),
    'retry_after' => config('shared-jobs.retry_after'),
    'after_commit' => true,
]);
```

### 2. Facade (`SharedJob`)

Proxies to `SharedJobDispatcherInterface`. Must support `fake()` for testing:

```php
SharedJob::dispatch(string $name, array $payload = []): void
SharedJob::dispatchAndWait(string $name, array $payload = [], ?int $timeout = null): array
SharedJob::fake(): SharedJobFake
SharedJob::assertDispatched(string $name, ?Closure $callback = null): void
SharedJob::assertNotDispatched(string $name): void
SharedJob::assertNothingDispatched(): void
SharedJob::assertDispatchedTimes(string $name, int $times): void
```

### 3. ProcessSharedJob (Internal Job Class)

This is the Laravel queue job stored in `shared_jobs` table. Both apps require the package, so serialization works because the class exists in both.

```php
final class ProcessSharedJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly array $payload,
        public readonly string $jobId,
        public readonly CarbonImmutable $dispatchedAt,
    ) {
        $this->onConnection(config('shared-jobs.connection'));
        $this->onQueue(config('shared-jobs.queue'));
    }

    public function handle(): void
    {
        event(new SharedJobReceived(
            name: $this->name,
            payload: $this->payload,
            jobId: $this->jobId,
            dispatchedAt: $this->dispatchedAt,
        ));
    }

    public function tries(): int
    {
        return (int) config('shared-jobs.tries', 3);
    }

    public function backoff(): int
    {
        return (int) config('shared-jobs.backoff', 0);
    }
}
```

### 4. SharedJobReceived Event

```php
final readonly class SharedJobReceived
{
    public function __construct(
        public string $name,
        public array $payload,
        public string $jobId,
        public CarbonImmutable $dispatchedAt,
    ) {}

    /**
     * For dispatchAndWait() — writes result back to shared_job_results table.
     *
     * @param array<string, mixed> $data
     */
    public function respond(array $data): void
    {
        // Implementation: update shared_job_results table with result
    }
}
```

### 5. SharedJobListener (Optional Abstract Base Class)

Users can extend this instead of manually checking `$event->name`:

```php
abstract class SharedJobListener
{
    protected string $jobName;

    public function handle(SharedJobReceived $event): void
    {
        if ($event->name !== $this->jobName) {
            return;
        }

        $this->process($event->payload, $event);
    }

    /**
     * @param array<string, mixed> $payload
     */
    abstract public function process(array $payload, SharedJobReceived $event): void;
}
```

### 6. SharedJobFake (Testing Support)

Follows Laravel's fake pattern (`Bus::fake()`, `Event::fake()`):

```php
SharedJob::fake();

// ... code that dispatches ...

SharedJob::assertDispatched('refund', function (string $name, array $payload): bool {
    return $payload['account_id'] === 5;
});

SharedJob::assertNotDispatched('suspend-account');
SharedJob::assertNothingDispatched();
SharedJob::assertDispatchedTimes('refund', 2);
```

### 7. Migration

**EXACTLY** Laravel's own queue jobs table structure. No custom columns. Because we use the native `database` driver, the table format must match Laravel's expectations:

```php
Schema::create(config('shared-jobs.table', 'shared_jobs'), function (Blueprint $table) {
    $table->id();
    $table->string('queue')->index();
    $table->longText('payload');
    $table->unsignedTinyInteger('attempts')->default(0);
    $table->unsignedInteger('reserved_at')->nullable();
    $table->unsignedInteger('available_at');
    $table->unsignedInteger('created_at');
});
```

### 8. dispatchAndWait() Support

Needs a secondary `shared_job_results` table for synchronous-like behavior:

```php
Schema::create('shared_job_results', function (Blueprint $table) {
    $table->uuid('job_id')->primary();
    $table->json('result')->nullable();
    $table->string('status'); // pending, completed, failed
    $table->text('error')->nullable();
    $table->timestamps();
});
```

Flow: Dispatcher inserts `pending` row → consumer's `respond()` updates to `completed` with result → dispatcher polls until status changes or timeout throws `SharedJobTimeoutException`.

## Tooling Config Files

### composer.json

```json
{
    "name": "laratusk/shared-jobs",
    "description": "Cross-application job dispatch via shared database for Laravel",
    "type": "library",
    "license": "MIT",
    "keywords": ["laravel", "queue", "shared", "jobs", "cross-app", "dispatch"],
    "require": {
        "php": "^8.2",
        "illuminate/contracts": "^11.0|^12.0",
        "illuminate/queue": "^11.0|^12.0",
        "illuminate/support": "^11.0|^12.0",
        "illuminate/events": "^11.0|^12.0",
        "illuminate/database": "^11.0|^12.0"
    },
    "require-dev": {
        "larastan/larastan": "^3.0",
        "laravel/pint": "^1.18",
        "orchestra/testbench": "^9.0|^10.0",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0",
        "rector/rector": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Laratusk\\SharedJobs\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Laratusk\\SharedJobs\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "pest",
        "test:coverage": "pest --coverage --min=80",
        "analyse": "phpstan analyse",
        "lint": "pint",
        "lint:check": "pint --test",
        "rector": "rector process",
        "rector:dry": "rector process --dry-run",
        "quality": [
            "@lint:check",
            "@analyse",
            "@rector:dry",
            "@test"
        ]
    },
    "extra": {
        "laravel": {
            "providers": [
                "Laratusk\\SharedJobs\\SharedJobsServiceProvider"
            ],
            "aliases": {
                "SharedJob": "Laratusk\\SharedJobs\\Facades\\SharedJob"
            }
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### phpstan.neon

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - src/
    level: 9
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    reportUnmatchedIgnoredErrors: true
    treatPhpDocTypesAsCertain: false
```

### rector.php

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Laravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        LevelSetList::UP_TO_PHP_82,
        LaravelSetList::LARAVEL_110,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
```

### pint.json

```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "final_class": true,
        "global_namespace_import": {
            "import_classes": true,
            "import_constants": true,
            "import_functions": true
        },
        "ordered_imports": {
            "sort_algorithm": "alpha"
        },
        "no_unused_imports": true,
        "trailing_comma_in_multiline": true
    }
}
```

### .gitattributes

```gitattributes
/.github/              export-ignore
/tests/                export-ignore
/.gitattributes        export-ignore
/.gitignore            export-ignore
/phpstan.neon          export-ignore
/phpunit.xml           export-ignore
/rector.php            export-ignore
/pint.json             export-ignore
/CHANGELOG.md          export-ignore
/.editorconfig         export-ignore
/Makefile              export-ignore
/testbench.yaml        export-ignore

*.png binary
*.jpg binary
*.gif binary
*.ico binary

* text=auto eol=lf
*.blade.php text eol=lf
```

### .gitignore

```gitignore
/vendor/
/node_modules/
composer.lock
.idea/
.vscode/
*.swp
*.swo
*~
.DS_Store
Thumbs.db
.phpunit.result.cache
.phpunit.cache/
/coverage/
/build/
.env
.env.backup
.env.production
/workbench/
/.testbench/
/.rector/
```

### GitHub Actions CI (.github/workflows/ci.yml)

```yaml
name: CI

on:
    push:
        branches: [main, develop]
    pull_request:
        branches: [main, develop]

jobs:
    tests:
        runs-on: ubuntu-latest

        strategy:
            fail-fast: true
            matrix:
                php: ['8.2', '8.3', '8.4']
                laravel: ['11.*', '12.*']
                include:
                    - laravel: '11.*'
                      testbench: '9.*'
                    - laravel: '12.*'
                      testbench: '10.*'

        name: PHP ${{ matrix.php }} - Laravel ${{ matrix.laravel }}

        steps:
            - uses: actions/checkout@v4

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: ${{ matrix.php }}
                  extensions: mbstring, dom, fileinfo
                  coverage: xdebug

            - name: Install dependencies
              run: |
                  composer require "laravel/framework:${{ matrix.laravel }}" "orchestra/testbench:${{ matrix.testbench }}" --no-interaction --no-update
                  composer update --prefer-stable --prefer-dist --no-interaction

            - name: Check code style
              run: vendor/bin/pint --test

            - name: Run Rector (dry-run)
              run: vendor/bin/rector process --dry-run

            - name: Run PHPStan
              run: vendor/bin/phpstan analyse --no-progress

            - name: Run tests
              run: vendor/bin/pest --coverage --min=80
```

## Critical Rules

1. **No custom queue driver** — use Laravel's native `database` driver pointed at a different table
2. **No magic** — everything is standard Laravel: queue worker, event system, serialization
3. **Everything configurable via .env** — connection, queue, table, timeouts, retries, role
4. **Both apps require the package** — `ProcessSharedJob` class exists in both, serialization works
5. **Role-based behavior** — `dispatcher` only dispatches, `consumer` only consumes, `both` does everything
6. **Facade must support `fake()`** — testing is first-class citizen
7. **All classes `final` or `readonly`**, strict_types everywhere, PHPStan level 9
8. **Worker command is standard Laravel** — `php artisan queue:work shared-jobs --queue=shared`

## Workflow Order

1. **Scaffold** — Generate directory structure and ALL config files first
2. **Contracts first** — `SharedJobDispatcherInterface`
3. **DTOs & Enums** — `SharedJobPayload`, `Role`
4. **Events** — `SharedJobReceived`
5. **Exceptions** — `SharedJobException`, `SharedJobTimeoutException`
6. **Jobs** — `ProcessSharedJob`
7. **Services** — `SharedJobDispatcher`
8. **Support** — `SharedJobFake`
9. **Listeners** — `SharedJobListener` abstract base
10. **ServiceProvider** — wire everything
11. **Facade** — `SharedJob`
12. **Migration** — standard Laravel jobs table
13. **Console Commands** — `SharedJobsTableCommand`
14. **Tests** — every public method tested, aim 80%+ coverage
15. **README.md** — full documentation with examples

**Start building. Every file must be complete — no placeholders, no snippets, no TODOs.**