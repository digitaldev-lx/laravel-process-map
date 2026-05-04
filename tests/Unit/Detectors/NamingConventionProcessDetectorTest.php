<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapSummary;
use DigitaldevLx\LaravelProcessMap\Detectors\NamingConventionProcessDetector;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;

function makeClass(ScannerType $type, string $shortName, string $fqcn): DiscoveredClass
{
    return new DiscoveredClass(
        type: $type,
        className: $fqcn,
        shortName: $shortName,
        namespace: 'App',
        filePath: 'app/'.$shortName.'.php',
    );
}

it('groups classes by entity once at least two classes share the same root', function (): void {
    $result = new ProcessMapResult(
        generatedAt: 'now',
        packageName: 'demo',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary,
        classes: [
            makeClass(ScannerType::Action, 'CreateLeadAction', 'App\\Actions\\CreateLeadAction'),
            makeClass(ScannerType::Job, 'SendLeadFollowUpJob', 'App\\Jobs\\SendLeadFollowUpJob'),
            makeClass(ScannerType::Event, 'LeadCreated', 'App\\Events\\LeadCreated'),
            makeClass(ScannerType::Notification, 'LeadAssignedNotification', 'App\\Notifications\\LeadAssignedNotification'),

            makeClass(ScannerType::Action, 'CreateBookingAction', 'App\\Actions\\CreateBookingAction'),
            makeClass(ScannerType::Action, 'CancelBookingAction', 'App\\Actions\\CancelBookingAction'),
            makeClass(ScannerType::Notification, 'BookingConfirmedNotification', 'App\\Notifications\\BookingConfirmedNotification'),

            makeClass(ScannerType::Action, 'OrphanAction', 'App\\Actions\\OrphanAction'),
        ],
    );

    $detector = new NamingConventionProcessDetector;
    $processes = $detector->detect($result);

    $names = array_map(static fn ($p) => $p->name, $processes);
    expect($names)->toContain('Lead Management', 'Booking Management');
    expect($names)->not->toContain('Orphan Management');
});

it('groups Policy classes into the policies bucket (B1 regression)', function (): void {
    $result = new ProcessMapResult(
        generatedAt: 'now',
        packageName: 'demo',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary,
        classes: [
            makeClass(ScannerType::Action, 'CreateOrderAction', 'App\\Actions\\CreateOrderAction'),
            makeClass(ScannerType::Policy, 'OrderPolicy', 'App\\Policies\\OrderPolicy'),
        ],
    );

    $processes = (new NamingConventionProcessDetector)->detect($result);

    expect($processes)->toHaveCount(1);
    expect($processes[0]->components['policies'])->toContain('App\\Policies\\OrderPolicy');
    expect($processes[0]->components)->toHaveKey('policies');
    expect($processes[0]->components)->not->toHaveKey('policys');
});

it('strips technical suffixes even when the user shrinks the business suffix list (B2 regression)', function (): void {
    $detector = new NamingConventionProcessDetector(
        verbs: ['create'],
        businessSuffixes: ['Action'],
    );

    $result = new ProcessMapResult(
        generatedAt: 'now',
        packageName: 'demo',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary,
        classes: [
            makeClass(ScannerType::Controller, 'OrderController', 'App\\Http\\Controllers\\OrderController'),
            makeClass(ScannerType::Action, 'CreateOrderAction', 'App\\Actions\\CreateOrderAction'),
            makeClass(ScannerType::Policy, 'OrderPolicy', 'App\\Policies\\OrderPolicy'),
            makeClass(ScannerType::Listener, 'OrderListener', 'App\\Listeners\\OrderListener'),
        ],
    );

    $processes = $detector->detect($result);

    expect($processes)->toHaveCount(1);
    expect($processes[0]->name)->toBe('Order Management');
    expect($processes[0]->components['controllers'])->toContain('App\\Http\\Controllers\\OrderController');
    expect($processes[0]->components['policies'])->toContain('App\\Policies\\OrderPolicy');
    expect($processes[0]->components['listeners'])->toContain('App\\Listeners\\OrderListener');
});

it('drops processes that only have a single class', function (): void {
    $result = new ProcessMapResult(
        generatedAt: 'now',
        packageName: 'demo',
        packageVersion: '0.1.0-dev',
        app: ['name' => 'demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        summary: new ProcessMapSummary,
        classes: [
            makeClass(ScannerType::Action, 'OrphanAction', 'App\\Actions\\OrphanAction'),
        ],
    );

    $processes = (new NamingConventionProcessDetector)->detect($result);

    expect($processes)->toBe([]);
});
