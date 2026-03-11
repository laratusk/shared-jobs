<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Contracts;

interface SharedJobDispatcherInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $name, array $payload = []): void;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function dispatchAndWait(string $name, array $payload = [], ?int $timeout = null): array;
}
