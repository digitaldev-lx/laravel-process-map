<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use DigitaldevLx\LaravelProcessMap\Mcp\Support\ProcessMapRepository;
use DigitaldevLx\LaravelProcessMap\Mcp\Support\ProcessMapSanitizer;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;

function makeRepo(string $tmp, array $configOverrides = []): ProcessMapRepository
{
    $config = new ConfigRepository(array_merge([
        'process-map' => [
            'output_path' => $tmp,
            'mcp' => [
                'cache' => ['enabled' => false, 'ttl_seconds' => 300],
                'security' => ['redact_sensitive_values' => true],
            ],
        ],
    ], $configOverrides));

    $cache = new CacheRepository(new ArrayStore);

    return new ProcessMapRepository($config, $cache, new ProcessMapSanitizer($config));
}

beforeEach(function (): void {
    $this->tmp = sys_get_temp_dir().'/process-map-repo-'.uniqid();
    mkdir($this->tmp, 0777, true);
});

afterEach(function (): void {
    exec('rm -rf '.escapeshellarg($this->tmp));
});

it('throws a clear exception when process-map.json is missing', function (): void {
    $repo = makeRepo($this->tmp);

    expect(fn () => $repo->snapshot())
        ->toThrow(ProcessMapException::class, 'No process map found');
});

it('loads, parses and exposes the snapshot when the JSON exists', function (): void {
    file_put_contents($this->tmp.'/process-map.json', json_encode([
        'schema_version' => '0.1',
        'generated_at' => '2026-05-04T12:00:00+00:00',
        'package' => 'demo',
        'version' => '1.1.0',
        'app' => ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        'summary' => ['models' => 3, 'processes' => 1],
        'classes' => [
            ['type' => 'model', 'class_name' => 'App\\Models\\Lead', 'short_name' => 'Lead'],
            ['type' => 'action', 'class_name' => 'App\\Actions\\CreateLeadAction', 'short_name' => 'CreateLeadAction'],
        ],
        'routes' => [['methods' => ['POST'], 'uri' => 'leads', 'name' => 'leads.store']],
        'processes' => [[
            'name' => 'Lead Management',
            'slug' => 'lead-management',
            'entity' => 'Lead',
            'risks' => ['Missing policy.'],
            'recommendations' => ['Add policy.'],
            'potential_bottlenecks' => ['Synchronous notifications.'],
        ]],
    ]));

    $repo = makeRepo($this->tmp);

    expect($repo->summary()['totals']['models'])->toBe(3);
    expect($repo->processes())->toHaveCount(1);
    expect($repo->classesByType())->toHaveKeys(['model', 'action']);
    expect($repo->routes())->toHaveCount(1);

    $risks = $repo->risks();
    expect($risks)->toHaveCount(1);
    expect($risks[0]['process'])->toBe('Lead Management');
    expect($risks[0]['slug'])->toBe('lead-management');

    $recs = $repo->recommendations();
    expect($recs)->toHaveCount(2); // recommendation + bottleneck
});

it('finds a process by slug, name or entity (case-insensitive)', function (): void {
    file_put_contents($this->tmp.'/process-map.json', json_encode([
        'processes' => [
            ['name' => 'Lead Management', 'slug' => 'lead-management', 'entity' => 'Lead'],
        ],
    ]));

    $repo = makeRepo($this->tmp);

    expect($repo->findProcess('lead-management')['name'])->toBe('Lead Management');
    expect($repo->findProcess('Lead Management')['slug'])->toBe('lead-management');
    expect($repo->findProcess('lead')['entity'])->toBe('Lead');
    expect($repo->findProcess('nope'))->toBeNull();
});
