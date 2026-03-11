<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Laratusk\SharedJobs\Contracts\SharedJobDispatcherInterface;
use Laratusk\SharedJobs\Support\SharedJobFake;

/**
 * @method static void dispatch(string $name, array<string, mixed> $payload = [])
 * @method static array<string, mixed> dispatchAndWait(string $name, array<string, mixed> $payload = [], ?int $timeout = null)
 * @method static void assertDispatched(string $name, ?Closure $callback = null)
 * @method static void assertNotDispatched(string $name)
 * @method static void assertNothingDispatched()
 * @method static void assertDispatchedTimes(string $name, int $times)
 *
 * @see \Laratusk\SharedJobs\Services\SharedJobDispatcher
 * @see \Laratusk\SharedJobs\Support\SharedJobFake
 */
final class SharedJob extends Facade
{
    public static function fake(): SharedJobFake
    {
        $fake = new SharedJobFake;

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return SharedJobDispatcherInterface::class;
    }
}
