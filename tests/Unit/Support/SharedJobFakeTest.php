<?php

declare(strict_types=1);

use Laratusk\SharedJobs\Support\SharedJobFake;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

it('records dispatched jobs', function (): void {
    $fake = new SharedJobFake;
    $fake->dispatch('refund', ['account_id' => 5]);

    $fake->assertDispatched('refund');
});

it('asserts dispatched with callback', function (): void {
    $fake = new SharedJobFake;
    $fake->dispatch('refund', ['account_id' => 5]);

    $fake->assertDispatched('refund', function (string $name, array $payload): bool {
        return $payload['account_id'] === 5;
    });
});

it('fails when asserting dispatched with unmet callback', function (): void {
    $fake = new SharedJobFake;
    $fake->dispatch('refund', ['account_id' => 5]);

    $fake->assertDispatched('refund', function (string $name, array $payload): bool {
        return $payload['account_id'] === 999;
    });
})->throws(ExpectationFailedException::class);

it('asserts not dispatched', function (): void {
    $fake = new SharedJobFake;
    $fake->dispatch('refund', ['account_id' => 5]);

    $fake->assertNotDispatched('suspend-account');
});

it('fails when asserting not dispatched for dispatched job', function (): void {
    $fake = new SharedJobFake;
    $fake->dispatch('refund', ['account_id' => 5]);

    $fake->assertNotDispatched('refund');
})->throws(ExpectationFailedException::class);

it('asserts nothing dispatched', function (): void {
    $fake = new SharedJobFake;

    $fake->assertNothingDispatched();
});

it('fails when asserting nothing dispatched with jobs', function (): void {
    $fake = new SharedJobFake;
    $fake->dispatch('refund', ['account_id' => 5]);

    $fake->assertNothingDispatched();
})->throws(ExpectationFailedException::class);

it('asserts dispatched times', function (): void {
    $fake = new SharedJobFake;
    $fake->dispatch('refund', ['account_id' => 5]);
    $fake->dispatch('refund', ['account_id' => 10]);

    $fake->assertDispatchedTimes('refund', 2);
});

it('fails when asserting wrong dispatch count', function (): void {
    $fake = new SharedJobFake;
    $fake->dispatch('refund', ['account_id' => 5]);

    $fake->assertDispatchedTimes('refund', 3);
})->throws(ExpectationFailedException::class);

it('fails when asserting dispatched for non-dispatched job', function (): void {
    $fake = new SharedJobFake;

    $fake->assertDispatched('refund');
})->throws(ExpectationFailedException::class);

it('dispatchAndWait records the job and returns empty array', function (): void {
    $fake = new SharedJobFake;
    $result = $fake->dispatchAndWait('refund', ['account_id' => 5]);

    expect($result)->toBe([]);
    $fake->assertDispatched('refund');
});
