<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\EventScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new EventScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Events'],
    );
});

it('discovers events and reports broadcasting metadata', function (): void {
    $classes = $this->scanner->scan();

    expect($classes)->toHaveCount(1);

    $event = $classes[0];

    expect($event->type)->toBe(ScannerType::Event);
    expect($event->shortName)->toBe('LeadCreated');
    expect($event->metadata['should_broadcast'])->toBeFalse();
    expect($event->metadata['uses_dispatchable'])->toBeFalse();
});
