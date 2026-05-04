<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\JobScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new JobScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Jobs'],
    );
});

it('discovers jobs and resolves their queue metadata', function (): void {
    $jobs = $this->scanner->scan();
    $byName = collect($jobs)->keyBy('shortName');

    expect($byName)->toHaveKeys(['ProcessHeavyReportJob', 'SendLeadFollowUpJob']);

    $heavy = $byName['ProcessHeavyReportJob'];
    expect($heavy->type)->toBe(ScannerType::Job);
    expect($heavy->metadata['should_queue'])->toBeTrue();
    expect($heavy->metadata['queue'])->toBe('reports');
    expect($heavy->metadata['tries'])->toBe(5);
    expect($heavy->metadata['timeout'])->toBe(120);
    expect($heavy->metadata['backoff'])->toBe(30);

    $light = $byName['SendLeadFollowUpJob'];
    expect($light->metadata['should_queue'])->toBeFalse();
    expect($light->metadata['queue'])->toBeNull();
});
