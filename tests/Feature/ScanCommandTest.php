<?php

declare(strict_types=1);

beforeEach(function (): void {
    $base = __DIR__.'/../Fixtures';
    $tmp = sys_get_temp_dir().'/process-map-cli-'.uniqid();
    mkdir($tmp, 0777, true);

    config()->set('process-map.directories', [
        'models' => ['App/Models'],
        'controllers' => ['App/Http/Controllers'],
        'actions' => ['App/Actions'],
        'jobs' => ['App/Jobs'],
        'events' => ['App/Events'],
        'listeners' => ['App/Listeners'],
        'notifications' => ['App/Notifications'],
        'policies' => ['App/Policies'],
        'commands' => ['App/Console/Commands'],
    ]);

    config()->set('process-map.scan.schedule', false);
    config()->set('process-map.output_path', $tmp);

    app()->setBasePath($base);

    $this->outputDir = $tmp;
});

afterEach(function (): void {
    exec('rm -rf '.escapeshellarg($this->outputDir));
});

it('produces JSON, Markdown and Mermaid artefacts when --all is passed', function (): void {
    $this->artisan('process-map:scan', ['--all' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain('Scanned:')
        ->expectsOutputToContain('Detected processes:');

    expect(file_exists($this->outputDir.'/process-map.json'))->toBeTrue();
    expect(file_exists($this->outputDir.'/process-map.md'))->toBeTrue();
    expect(file_exists($this->outputDir.'/process-map.mmd'))->toBeTrue();

    $json = file_get_contents($this->outputDir.'/process-map.json');
    expect($json)->not->toBeFalse();
    expect((string) $json)->toContain('"schema_version": "0.1"');
});

it('runs the report command and writes only the Markdown artefact', function (): void {
    $this->artisan('process-map:report')->assertExitCode(0);

    expect(file_exists($this->outputDir.'/process-map.md'))->toBeTrue();
    expect(file_exists($this->outputDir.'/process-map.json'))->toBeFalse();
});

it('honours --no-process-detection by skipping the heuristic clustering', function (): void {
    $this->artisan('process-map:scan', ['--json' => true, '--no-process-detection' => true])
        ->assertExitCode(0);

    $json = file_get_contents($this->outputDir.'/process-map.json');
    expect($json)->not->toBeFalse();

    $decoded = json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);
    expect($decoded['processes'])->toBe([]);
});
