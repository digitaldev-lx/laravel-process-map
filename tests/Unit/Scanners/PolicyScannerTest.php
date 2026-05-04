<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\PolicyScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new PolicyScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Policies'],
    );
});

it('discovers policies and splits standard abilities from custom ones', function (): void {
    $classes = $this->scanner->scan();

    expect($classes)->toHaveCount(1);

    $policy = $classes[0];

    expect($policy->type)->toBe(ScannerType::Policy);
    expect($policy->shortName)->toBe('LeadPolicy');
    expect($policy->metadata['model'])->toBe('Lead');
    expect($policy->metadata['standard_abilities'])->toContain('viewAny', 'update');
    expect($policy->metadata['custom_abilities'])->toContain('assignToAgent');
    expect($policy->metadata['custom_abilities'])->not->toContain('viewAny');
});
