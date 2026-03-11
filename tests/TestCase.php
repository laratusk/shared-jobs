<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Tests;

use Laratusk\SharedJobs\SharedJobsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SharedJobsServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'SharedJob' => \Laratusk\SharedJobs\Facades\SharedJob::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
