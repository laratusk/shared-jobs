<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Contracts;

interface SharedJobResponderInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function respond(string $jobId, array $data): void;
}
