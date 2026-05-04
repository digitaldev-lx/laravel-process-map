<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\ScheduleScanner;
use Illuminate\Console\Scheduling\Schedule;

it('returns metadata for every registered scheduled task', function (): void {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $schedule->command('leads:sync')
        ->hourly()
        ->description('Sync leads from CRM');

    $schedule->command('reports:build')
        ->dailyAt('03:00')
        ->withoutOverlapping();

    $scanner = new ScheduleScanner($schedule);

    expect($scanner->type())->toBe(ScannerType::Schedule);

    $entries = $scanner->scan();

    expect($entries)->toHaveCount(2);

    $sync = collect($entries)->firstWhere('description', 'Sync leads from CRM');

    expect($sync)->not->toBeNull();
    expect($sync['expression'])->toBe('0 * * * *');
    expect($sync['command'])->toContain('leads:sync');

    $reports = collect($entries)->firstWhere('expression', '0 3 * * *');

    expect($reports['without_overlapping'])->toBeTrue();
});
