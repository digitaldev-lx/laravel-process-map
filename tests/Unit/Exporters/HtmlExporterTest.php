<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapSummary;
use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use DigitaldevLx\LaravelProcessMap\Exporters\HtmlExporter;

it('throws a clear exception until the HTML dashboard ships in v0.2', function (): void {
    $result = new ProcessMapResult(
        generatedAt: 'now',
        packageName: 'demo',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary,
    );

    expect(fn (): mixed => (new HtmlExporter)->export($result))
        ->toThrow(ProcessMapException::class);
});
