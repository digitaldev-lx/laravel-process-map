<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredRelation;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredRoute;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapSummary;
use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;

function makeResult(array $processes = []): ProcessMapResult
{
    return new ProcessMapResult(
        generatedAt: '2026-05-04T12:00:00+00:00',
        packageName: 'digitaldev-lx/laravel-process-map',
        packageVersion: '0.1.0-dev',
        app: [
            'name' => 'Demo',
            'environment' => 'testing',
            'laravel_version' => '13.x',
            'php_version' => PHP_VERSION,
        ],
        summary: new ProcessMapSummary(models: 1, controllers: 1, actions: 1, routes: 1),
        classes: [
            new DiscoveredClass(
                type: ScannerType::Action,
                className: 'App\\Actions\\CreateLeadAction',
                shortName: 'CreateLeadAction',
                namespace: 'App\\Actions',
                filePath: 'app/Actions/CreateLeadAction.php',
                methods: ['__invoke'],
            ),
        ],
        routes: [
            new DiscoveredRoute(
                methods: ['POST'],
                uri: 'leads',
                name: 'leads.store',
                action: 'App\\Http\\Controllers\\LeadController@store',
                controllerClass: 'App\\Http\\Controllers\\LeadController',
                controllerMethod: 'store',
                middleware: ['web', 'auth'],
            ),
        ],
        processes: $processes,
    );
}

it('serialises every part of the result to a stable array shape', function (): void {
    $result = makeResult();

    $array = $result->toArray();

    expect($array['schema_version'])->toBe('0.1');
    expect($array['package'])->toBe('digitaldev-lx/laravel-process-map');
    expect($array['summary']['models'])->toBe(1);
    expect($array['classes'])->toHaveCount(1);
    expect($array['classes'][0]['type'])->toBe('action');
    expect($array['routes'][0]['controller_class'])->toBe('App\\Http\\Controllers\\LeadController');
});

it('returns a copy with new processes and updates the summary count', function (): void {
    $result = makeResult();

    $process = new DiscoveredProcess(
        name: 'Lead Management',
        entity: 'Lead',
        automationLevel: AutomationLevel::High,
        components: ['actions' => ['App\\Actions\\CreateLeadAction']],
        relations: [new DiscoveredRelation('A', 'B', 'dispatches')],
    );

    $updated = $result->withProcesses([$process], ['add notification']);

    expect($updated)->not->toBe($result);
    expect($updated->summary->processes)->toBe(1);
    expect($updated->processes)->toHaveCount(1);
    expect($updated->recommendations)->toBe(['add notification']);

    $serialised = $updated->toArray();
    expect($serialised['processes'][0]['name'])->toBe('Lead Management');
    expect($serialised['processes'][0]['relations'][0]['type'])->toBe('dispatches');
});
