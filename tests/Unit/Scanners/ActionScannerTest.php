<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\ActionScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new ActionScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Actions'],
    );
});

it('discovers action classes and detects the main entry method', function (): void {
    $classes = $this->scanner->scan();

    expect($classes)->toHaveCount(1);

    $action = $classes[0];

    expect($action->type)->toBe(ScannerType::Action);
    expect($action->shortName)->toBe('CreateLeadAction');
    expect($action->metadata['main_method'])->toBe('__invoke');
});

it('detects Bus::dispatch(new Job) calls (M4)', function (): void {
    $tmp = sys_get_temp_dir().'/process-map-bus-action-'.uniqid();
    mkdir($tmp.'/Actions', 0777, true);

    file_put_contents($tmp.'/Actions/BulkSendAction.php', <<<'PHP'
        <?php
        namespace TestFixturesBus\Actions;

        use Illuminate\Support\Facades\Bus;

        class BulkSendAction
        {
            public function __invoke(): void
            {
                Bus::dispatch(new \TestFixturesBus\Jobs\HeavyJob());
            }
        }
        PHP);

    $scanner = new ActionScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [$tmp.'/Actions'],
    );

    $found = $scanner->scan();

    expect($found)->toHaveCount(1);
    expect($found[0]->metadata['dispatches_jobs'])->toContain('TestFixturesBus\\Jobs\\HeavyJob');

    exec('rm -rf '.escapeshellarg($tmp));
});

it('records dispatched jobs and fired events separately', function (): void {
    $action = $this->scanner->scan()[0];

    expect($action->metadata['dispatches_jobs'])->toContain('Tests\\Fixtures\\App\\Jobs\\SendLeadFollowUpJob');
    expect($action->metadata['fires_events'])->toContain('Tests\\Fixtures\\App\\Events\\LeadCreated');
});
