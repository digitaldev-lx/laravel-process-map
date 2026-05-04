<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapSummary;
use DigitaldevLx\LaravelProcessMap\Exporters\JsonExporter;

function emptyResult(): ProcessMapResult
{
    return new ProcessMapResult(
        generatedAt: '2026-05-04T12:00:00+00:00',
        packageName: 'digitaldev-lx/laravel-process-map',
        packageVersion: '0.1.0-dev',
        app: [
            'name' => 'Demo',
            'environment' => 'testing',
            'laravel_version' => '13.0',
            'php_version' => '8.4',
        ],
        summary: new ProcessMapSummary(models: 1),
    );
}

it('renders a stable JSON structure with the schema version', function (): void {
    $json = (new JsonExporter)->export(emptyResult());

    $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    expect($decoded['schema_version'])->toBe('0.1');
    expect($decoded['package'])->toBe('digitaldev-lx/laravel-process-map');
    expect($decoded['summary']['models'])->toBe(1);
});

it('writes the JSON to disk when a path is supplied', function (): void {
    $path = sys_get_temp_dir().'/process-map-tests/export.json';
    @unlink($path);

    (new JsonExporter)->export(emptyResult(), $path);

    expect(file_exists($path))->toBeTrue();
    $contents = file_get_contents($path);
    expect($contents)->not->toBeFalse();
    expect((string) $contents)->toContain('"schema_version": "0.1"');

    @unlink($path);
});
