<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Detectors\AutomationDetector;
use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;

function processWith(array $components): DiscoveredProcess
{
    return new DiscoveredProcess(
        name: 'Test',
        entity: 'Test',
        automationLevel: AutomationLevel::None,
        components: $components,
    );
}

it('classifies a model-only process as None', function (): void {
    $result = (new AutomationDetector)->classify([
        processWith(['models' => ['App\\Models\\Foo']]),
    ]);

    expect($result[0]->automationLevel)->toBe(AutomationLevel::None);
});

it('classifies a fully-wired process (actions+jobs+events+listeners+notifications) as High', function (): void {
    $result = (new AutomationDetector)->classify([
        processWith([
            'actions' => ['A'],
            'jobs' => ['J'],
            'events' => ['E'],
            'listeners' => ['L'],
            'notifications' => ['N'],
        ]),
    ]);

    expect($result[0]->automationLevel)->toBe(AutomationLevel::High);
});

it('counts schedule and broadcasts buckets towards the score (B3 regression)', function (): void {
    $result = (new AutomationDetector)->classify([
        processWith([
            'actions' => ['A'],
            'schedule' => ['leads:sync'],
            'broadcasts' => ['leads']],
        ),
    ]);

    expect($result[0]->automationLevel)->toBe(AutomationLevel::Medium);
});
