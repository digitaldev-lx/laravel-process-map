<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\CommandScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new CommandScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Console/Commands'],
    );
});

it('discovers Artisan commands and reads their signature and description', function (): void {
    $classes = $this->scanner->scan();

    expect($classes)->toHaveCount(1);

    $command = $classes[0];

    expect($command->type)->toBe(ScannerType::Command);
    expect($command->shortName)->toBe('SyncLeadsCommand');
    expect($command->metadata['signature'])->toBe('leads:sync {--since=}');
    expect($command->metadata['description'])->toBe('Pull recent leads from the upstream CRM');
    expect($command->metadata['extends_command'])->toBeTrue();
});
