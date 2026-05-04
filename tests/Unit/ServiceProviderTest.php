<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\ProcessMap;
use DigitaldevLx\LaravelProcessMap\Providers\ProcessMapServiceProvider;
use Illuminate\Support\ServiceProvider;

it('merges the package config under the process-map key', function (): void {
    expect(config('process-map.scan.models'))->toBeTrue();
    expect(config('process-map.process_detection.enabled'))->toBeTrue();
});

it('binds the ProcessMap manager as a singleton', function (): void {
    $first = app(ProcessMap::class);
    $second = app(ProcessMap::class);

    expect($first)->toBe($second);
    expect($first->version())->toBeString()->not->toBe('');
});

it('publishes the config file under the process-map-config tag', function (): void {
    $registered = collect(ServiceProvider::pathsToPublish(
        ProcessMapServiceProvider::class,
        'process-map-config'
    ));

    expect($registered)->not->toBeEmpty();
});
