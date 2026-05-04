<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\ListenerScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new ListenerScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Listeners'],
    );
});

it('discovers listeners and resolves the listened event from the handle signature', function (): void {
    $classes = $this->scanner->scan();

    expect($classes)->toHaveCount(1);

    $listener = $classes[0];

    expect($listener->type)->toBe(ScannerType::Listener);
    expect($listener->shortName)->toBe('SendLeadCreatedNotification');
    expect($listener->metadata['listens_to'])->toBe('Tests\\Fixtures\\App\\Events\\LeadCreated');
    expect($listener->metadata['should_queue'])->toBeTrue();
});
