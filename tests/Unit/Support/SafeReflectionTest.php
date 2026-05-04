<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Support\SafeReflection;

it('returns null for unknown classes without raising', function (): void {
    $reflector = new SafeReflection;

    expect($reflector->reflect('Acme\\Does\\Not\\Exist'))->toBeNull();
    expect($reflector->interfaceNames('Acme\\Nope'))->toBe([]);
    expect($reflector->traitNames('Acme\\Nope'))->toBe([]);
    expect($reflector->parentName('Acme\\Nope'))->toBeNull();
});

it('reflects a real class', function (): void {
    $reflector = new SafeReflection;

    expect($reflector->reflect(stdClass::class))->not->toBeNull();
    expect($reflector->parentName(RuntimeException::class))->toBe('Exception');
    expect($reflector->parentName(Exception::class))->toBeNull();
});

it('honours the disabled flag and skips reflection entirely', function (): void {
    $reflector = new SafeReflection(enabled: false);

    expect($reflector->reflect(stdClass::class))->toBeNull();
    expect($reflector->interfaceNames(ArrayObject::class))->toBe([]);
});
