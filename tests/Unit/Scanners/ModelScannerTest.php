<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\ModelScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new ModelScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Models'],
    );
});

it('emits a DiscoveredClass for every concrete model', function (): void {
    $classes = $this->scanner->scan();
    $names = array_map(static fn ($c) => $c->shortName, $classes);

    sort($names);

    expect($names)->toBe(['Booking', 'Lead']);

    foreach ($classes as $class) {
        expect($class->type)->toBe(ScannerType::Model);
    }
});

it('skips abstract models', function (): void {
    $names = array_map(static fn ($c) => $c->shortName, $this->scanner->scan());

    expect($names)->not->toContain('AbstractEntity');
});

it('skips trait declarations', function (): void {
    $names = array_map(static fn ($c) => $c->shortName, $this->scanner->scan());

    expect($names)->not->toContain('HasUuid');
});

it('captures Eloquent metadata for the Lead model', function (): void {
    $lead = collect($this->scanner->scan())->firstWhere('shortName', 'Lead');

    expect($lead)->not->toBeNull();
    expect($lead->metadata['table'])->toBe('leads');
    expect($lead->metadata['fillable'])->toBe(['name', 'email', 'status']);
    expect($lead->metadata['casts'])->toContain('string');
    expect($lead->metadata['soft_deletes'])->toBeTrue();
    expect($lead->metadata['has_factory'])->toBeTrue();
    expect($lead->metadata['extends_eloquent'])->toBeTrue();
});

it('still emits a model when traits and table are absent', function (): void {
    $booking = collect($this->scanner->scan())->firstWhere('shortName', 'Booking');

    expect($booking)->not->toBeNull();
    expect($booking->metadata['soft_deletes'])->toBeFalse();
    expect($booking->metadata['table'])->toBeNull();
});
