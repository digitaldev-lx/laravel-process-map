<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapSummary;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Exporters\JsonExporter;

function resultWithMethods(): ProcessMapResult
{
    return new ProcessMapResult(
        generatedAt: 'now',
        packageName: 'demo',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary(controllers: 1),
        classes: [
            new DiscoveredClass(
                type: ScannerType::Controller,
                className: 'App\\Http\\Controllers\\OrderController',
                shortName: 'OrderController',
                namespace: 'App\\Http\\Controllers',
                filePath: 'app/Http/Controllers/OrderController.php',
                methods: ['index', 'store'],
            ),
        ],
    );
}

it('redacts method names when privacy.include_method_names is false (M10)', function (): void {
    $exporter = new JsonExporter([
        'include_method_names' => false,
        'include_file_paths' => true,
    ]);

    $decoded = json_decode($exporter->export(resultWithMethods()), true);

    expect($decoded['classes'][0]['methods'])->toBe([]);
    expect($decoded['classes'][0]['file_path'])->toBe('app/Http/Controllers/OrderController.php');
});

it('redacts file paths when privacy.include_file_paths is false (M10)', function (): void {
    $exporter = new JsonExporter([
        'include_method_names' => true,
        'include_file_paths' => false,
    ]);

    $decoded = json_decode($exporter->export(resultWithMethods()), true);

    expect($decoded['classes'][0]['file_path'])->toBe('');
    expect($decoded['classes'][0]['methods'])->toContain('index', 'store');
});

it('keeps the full payload when privacy flags default to true', function (): void {
    $exporter = new JsonExporter;

    $decoded = json_decode($exporter->export(resultWithMethods()), true);

    expect($decoded['classes'][0]['methods'])->toContain('index', 'store');
    expect($decoded['classes'][0]['file_path'])->toBe('app/Http/Controllers/OrderController.php');
});
