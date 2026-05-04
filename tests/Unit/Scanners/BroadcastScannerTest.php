<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\BroadcastScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new BroadcastScanner(
        new NamespaceResolver,
        [__DIR__.'/../../Fixtures/routes/channels.php'],
    );
});

it('extracts every Broadcast::channel call', function (): void {
    $channels = $this->scanner->scan();

    expect($this->scanner->type())->toBe(ScannerType::Broadcast);
    expect($channels)->toHaveCount(2);

    $names = array_column($channels, 'name');

    expect($names)->toContain('App.Models.User.{id}', 'leads');

    foreach ($channels as $channel) {
        expect($channel['has_authorisation'])->toBeTrue();
        expect($channel['file'])->toBeString();
    }
});

it('returns an empty list when no files exist', function (): void {
    $scanner = new BroadcastScanner(
        new NamespaceResolver,
        ['/tmp/missing/channels.php'],
    );

    expect($scanner->scan())->toBe([]);
});
