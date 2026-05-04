<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\ControllerScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new ControllerScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Http/Controllers'],
    );
});

it('discovers controllers and lists only public action methods', function (): void {
    $classes = $this->scanner->scan();

    expect($classes)->toHaveCount(1);

    $controller = $classes[0];

    expect($controller->type)->toBe(ScannerType::Controller);
    expect($controller->shortName)->toBe('LeadController');
    expect($controller->methods)->toContain('index', 'store', 'destroy');
    expect($controller->methods)->not->toContain('audit');
});

it('lists form requests referenced from action signatures', function (): void {
    $controller = $this->scanner->scan()[0];

    expect($controller->metadata['form_requests'])->toContain(
        'Tests\\Fixtures\\App\\Http\\Requests\\StoreLeadRequest',
    );
});

it('records dispatched and fired references for downstream detectors', function (): void {
    $controller = $this->scanner->scan()[0];

    expect($controller->metadata['dispatched_classes'])->not->toBeEmpty();

    $events = array_column($controller->metadata['dispatched_classes'], 'class');

    expect($events)->toContain('Tests\\Fixtures\\App\\Events\\LeadCreated');
});
