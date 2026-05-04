<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredRoute;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapSummary;
use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Exporters\MermaidExporter;

it('produces a flowchart with controller/action/job edges', function (): void {
    $result = new ProcessMapResult(
        generatedAt: 'now',
        packageName: 'demo',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary(models: 0, controllers: 1, actions: 1, jobs: 1, processes: 1),
        classes: [
            new DiscoveredClass(
                type: ScannerType::Action,
                className: 'App\\Actions\\CreateLeadAction',
                shortName: 'CreateLeadAction',
                namespace: 'App\\Actions',
                filePath: 'app/Actions/CreateLeadAction.php',
                references: ['App\\Jobs\\SendLeadFollowUpJob'],
            ),
            new DiscoveredClass(
                type: ScannerType::Job,
                className: 'App\\Jobs\\SendLeadFollowUpJob',
                shortName: 'SendLeadFollowUpJob',
                namespace: 'App\\Jobs',
                filePath: 'app/Jobs/SendLeadFollowUpJob.php',
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
            ),
        ],
        processes: [
            new DiscoveredProcess(
                name: 'Lead Management',
                entity: 'Lead',
                automationLevel: AutomationLevel::Medium,
                components: [
                    'controllers' => ['App\\Http\\Controllers\\LeadController'],
                    'actions' => ['App\\Actions\\CreateLeadAction'],
                    'jobs' => ['App\\Jobs\\SendLeadFollowUpJob'],
                ],
            ),
        ],
    );

    $mermaid = (new MermaidExporter)->export($result);

    expect($mermaid)->toContain('flowchart TD');
    expect($mermaid)->toContain('App_Actions_CreateLeadAction');
    expect($mermaid)->toContain('App_Jobs_SendLeadFollowUpJob');
    expect($mermaid)->toContain('|invokes|');
    expect($mermaid)->toContain('|dispatches|');
});
