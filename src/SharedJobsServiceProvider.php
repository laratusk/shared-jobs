<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Laratusk\SharedJobs\Contracts\SharedJobDispatcherInterface;
use Laratusk\SharedJobs\Contracts\SharedJobResponderInterface;
use Laratusk\SharedJobs\Services\SharedJobDispatcher;
use Laratusk\SharedJobs\Services\SharedJobResponder;

final class SharedJobsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/shared-jobs.php', 'shared-jobs');

        /** @var Repository $config */
        $config = $this->app->make(Repository::class);

        /** @var string $connectionName */
        $connectionName = $config->get('shared-jobs.connection', 'shared-jobs');

        $config->set("queue.connections.{$connectionName}", [
            'driver' => 'database',
            'connection' => config('shared-jobs.database_connection'),
            'table' => config('shared-jobs.table', 'shared_jobs'),
            'queue' => config('shared-jobs.queue', 'shared'),
            'retry_after' => config('shared-jobs.retry_after', 90),
            'after_commit' => true,
        ]);

        $this->app->singleton(SharedJobDispatcherInterface::class, SharedJobDispatcher::class);
        $this->app->singleton(SharedJobResponderInterface::class, SharedJobResponder::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/shared-jobs.php' => config_path('shared-jobs.php'),
            ], 'shared-jobs-config');

            $this->publishesMigrations([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'shared-jobs-migrations');
        }
    }
}
