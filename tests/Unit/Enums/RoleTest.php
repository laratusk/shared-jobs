<?php

declare(strict_types=1);

use Laratusk\SharedJobs\Enums\Role;

it('has correct values', function (): void {
    expect(Role::Dispatcher->value)->toBe('dispatcher')
        ->and(Role::Consumer->value)->toBe('consumer')
        ->and(Role::Both->value)->toBe('both');
});

it('dispatcher can dispatch but not consume', function (): void {
    expect(Role::Dispatcher->canDispatch())->toBeTrue()
        ->and(Role::Dispatcher->canConsume())->toBeFalse();
});

it('consumer can consume but not dispatch', function (): void {
    expect(Role::Consumer->canConsume())->toBeTrue()
        ->and(Role::Consumer->canDispatch())->toBeFalse();
});

it('both can dispatch and consume', function (): void {
    expect(Role::Both->canDispatch())->toBeTrue()
        ->and(Role::Both->canConsume())->toBeTrue();
});

it('can be created from string value', function (): void {
    expect(Role::from('dispatcher'))->toBe(Role::Dispatcher)
        ->and(Role::from('consumer'))->toBe(Role::Consumer)
        ->and(Role::from('both'))->toBe(Role::Both);
});

it('throws for invalid value', function (): void {
    Role::from('invalid');
})->throws(ValueError::class);
