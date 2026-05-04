<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\ProcessMap;
use DigitaldevLx\LaravelProcessMap\Scanners\ApplicationScanner;

beforeEach(function (): void {
    $base = __DIR__.'/../Fixtures';

    config()->set('process-map.directories', [
        'models' => ['App/Models'],
        'controllers' => ['App/Http/Controllers'],
        'actions' => ['App/Actions'],
        'jobs' => ['App/Jobs'],
        'events' => ['App/Events'],
        'listeners' => ['App/Listeners'],
        'notifications' => ['App/Notifications'],
        'policies' => ['App/Policies'],
        'commands' => ['App/Console/Commands'],
    ]);

    config()->set('process-map.scan.schedule', false);
    config()->set('process-map.scan.broadcasting', true);

    app()->setBasePath($base);
});

it('aggregates every scanner output into a single ProcessMapResult', function (): void {
    /** @var ApplicationScanner $scanner */
    $scanner = app(ApplicationScanner::class);

    $result = $scanner->scan();

    expect($result->summary->models)->toBe(2);
    expect($result->summary->controllers)->toBe(1);
    expect($result->summary->actions)->toBe(1);
    expect($result->summary->jobs)->toBe(2);
    expect($result->summary->events)->toBe(1);
    expect($result->summary->listeners)->toBe(1);
    expect($result->summary->notifications)->toBe(1);
    expect($result->summary->policies)->toBe(1);
    expect($result->summary->commands)->toBe(1);

    expect($result->app['laravel_version'])->toBeString();
    expect($result->app['php_version'])->toBe(PHP_VERSION);
    expect($result->schemaVersion)->toBe('0.1');
});

it('exposes broadcast channels as schedule-like discovered classes', function (): void {
    config()->set('process-map.directories', ['models' => []]);

    /** @var ApplicationScanner $scanner */
    $scanner = app(ApplicationScanner::class);

    $result = $scanner->scan();

    $broadcasts = array_values(array_filter(
        $result->classes,
        static fn ($c) => $c->type === ScannerType::Broadcast,
    ));

    $names = array_map(static fn ($c) => $c->className, $broadcasts);

    expect($names)->toContain('App.Models.User.{id}', 'leads');
});

it('the ProcessMap singleton delegates to the application scanner', function (): void {
    config()->set('process-map.directories', ['models' => ['App/Models']]);

    /** @var ProcessMap $processMap */
    $processMap = app(ProcessMap::class);

    $result = $processMap->scan();

    expect($result->summary->models)->toBe(2);
    expect($processMap->version())->toBeString()->not->toBe('');
});
