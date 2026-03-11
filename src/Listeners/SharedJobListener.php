<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Listeners;

use Laratusk\SharedJobs\Events\SharedJobReceived;

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
     * @param  array<string, mixed>  $payload
     */
    abstract public function process(array $payload, SharedJobReceived $event): void;
}
