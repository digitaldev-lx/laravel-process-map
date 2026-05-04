<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapSummary;
use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;
use DigitaldevLx\LaravelProcessMap\Exporters\MarkdownExporter;

it('renders the dense LLM-friendly layout with every required section', function (): void {
    $result = new ProcessMapResult(
        generatedAt: '2026-05-04T12:00:00+00:00',
        packageName: 'digitaldev-lx/laravel-process-map',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary(models: 1, controllers: 1, processes: 1),
        processes: [
            new DiscoveredProcess(
                name: 'Lead Management',
                entity: 'Lead',
                automationLevel: AutomationLevel::High,
                components: ['actions' => ['App\\Actions\\CreateLeadAction']],
                potentialBottlenecks: ['Notifications may run synchronously'],
                risks: ['No policy attached'],
                recommendations: ['Add a policy for the Lead model.'],
            ),
        ],
    );

    $md = (new MarkdownExporter)->export($result);

    foreach ([
        '# Process Map: Demo',
        '## Summary',
        '## Processes',
        '### 1. Lead Management',
        '#### Members',
        '#### Findings',
        '⚠ Bottleneck:',
        '🛡 Risk:',
        '💡 Recommendation:',
    ] as $heading) {
        expect($md)->toContain($heading);
    }
});

it('renders an empty processes section when no clusters were detected', function (): void {
    $result = new ProcessMapResult(
        generatedAt: 'now',
        packageName: 'demo',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary,
    );

    $md = (new MarkdownExporter)->export($result);

    expect($md)->toContain('## Processes');
    expect($md)->toContain('_No processes detected by the naming-convention heuristic._');
});
