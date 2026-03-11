<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Support;

use Closure;
use Laratusk\SharedJobs\Contracts\SharedJobDispatcherInterface;
use PHPUnit\Framework\Assert;

final class SharedJobFake implements SharedJobDispatcherInterface
{
    /**
     * @var array<int, array{name: string, payload: array<string, mixed>}>
     */
    private array $dispatched = [];

    public function dispatch(string $name, array $payload = []): void
    {
        $this->dispatched[] = ['name' => $name, 'payload' => $payload];
    }

    public function dispatchAndWait(string $name, array $payload = [], ?int $timeout = null): array
    {
        $this->dispatched[] = ['name' => $name, 'payload' => $payload];

        return [];
    }

    public function assertDispatched(string $name, ?Closure $callback = null): void
    {
        $matching = $this->getDispatched($name);

        Assert::assertNotEmpty(
            $matching,
            "The expected shared job [{$name}] was not dispatched."
        );

        if ($callback !== null) {
            $filtered = array_filter(
                $matching,
                fn (array $job): bool => $callback($job['name'], $job['payload']),
            );

            Assert::assertNotEmpty(
                $filtered,
                "The expected shared job [{$name}] was dispatched, but the callback condition was not met."
            );
        }
    }

    public function assertNotDispatched(string $name): void
    {
        $matching = $this->getDispatched($name);

        Assert::assertEmpty(
            $matching,
            "The unexpected shared job [{$name}] was dispatched."
        );
    }

    public function assertNothingDispatched(): void
    {
        Assert::assertEmpty(
            $this->dispatched,
            'Shared jobs were dispatched unexpectedly: ' . implode(
                ', ',
                array_map(fn (array $job): string => $job['name'], $this->dispatched),
            )
        );
    }

    public function assertDispatchedTimes(string $name, int $times): void
    {
        $matching = $this->getDispatched($name);
        $count = count($matching);

        Assert::assertSame(
            $times,
            $count,
            "The expected shared job [{$name}] was dispatched {$count} times instead of {$times} times."
        );
    }

    /**
     * @return array<int, array{name: string, payload: array<string, mixed>}>
     */
    private function getDispatched(string $name): array
    {
        return array_values(
            array_filter(
                $this->dispatched,
                fn (array $job): bool => $job['name'] === $name,
            ),
        );
    }
}
