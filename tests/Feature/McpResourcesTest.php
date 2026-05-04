<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Mcp\Resources\ClassesResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\ProcessesResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\ProcessResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\RecommendationsResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\RisksResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\RoutesResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\SummaryResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function (): void {
    $this->tmp = sys_get_temp_dir().'/process-map-mcp-resources-'.uniqid();
    mkdir($this->tmp, 0777, true);

    file_put_contents($this->tmp.'/process-map.json', json_encode([
        'schema_version' => '0.1',
        'generated_at' => '2026-05-04T12:00:00+00:00',
        'package' => 'digitaldev-lx/laravel-process-map',
        'version' => '1.1.0',
        'app' => ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        'summary' => ['models' => 2, 'controllers' => 1, 'processes' => 1],
        'classes' => [
            ['type' => 'model', 'class_name' => 'App\\Models\\Lead', 'short_name' => 'Lead'],
            ['type' => 'controller', 'class_name' => 'App\\Http\\Controllers\\LeadController', 'short_name' => 'LeadController'],
        ],
        'routes' => [['methods' => ['POST'], 'uri' => 'leads', 'name' => 'leads.store', 'controller_class' => 'App\\Http\\Controllers\\LeadController', 'controller_method' => 'store', 'middleware' => ['web']]],
        'processes' => [[
            'name' => 'Lead Management',
            'slug' => 'lead-management',
            'entity' => 'Lead',
            'automation_level' => 'medium',
            'components' => ['models' => ['App\\Models\\Lead'], 'controllers' => ['App\\Http\\Controllers\\LeadController']],
            'risks' => ['Missing policy.'],
            'recommendations' => ['Add policy.'],
            'potential_bottlenecks' => [],
        ]],
    ]));

    config()->set('process-map.output_path', $this->tmp);
    config()->set('process-map.mcp.enabled', true);
    config()->set('process-map.mcp.cache.enabled', false);
});

afterEach(function (): void {
    exec('rm -rf '.escapeshellarg($this->tmp));
});

function decodeResource(Response $response): array
{
    $text = (string) $response->content();

    return json_decode($text, true) ?? [];
}

it('SummaryResource returns app metadata and totals', function (): void {
    $resource = app(SummaryResource::class);
    $payload = decodeResource($resource->handle(new Request));

    expect($payload['status'])->toBe('ok');
    expect($payload['data']['app']['name'])->toBe('Demo');
    expect($payload['data']['totals']['models'])->toBe(2);
});

it('ProcessesResource returns compact entries', function (): void {
    $resource = app(ProcessesResource::class);
    $payload = decodeResource($resource->handle(new Request));

    expect($payload['data']['count'])->toBe(1);
    expect($payload['data']['processes'][0]['slug'])->toBe('lead-management');
    expect($payload['data']['processes'][0]['risks_count'])->toBe(1);
});

it('ProcessResource returns full detail when slug matches', function (): void {
    $resource = app(ProcessResource::class);
    $request = new Request;
    $request->setArguments(['slug' => 'lead-management']);

    $payload = decodeResource($resource->handle($request));

    expect($payload['data']['process']['name'])->toBe('Lead Management');
});

it('ProcessResource returns null process for unknown slug', function (): void {
    $resource = app(ProcessResource::class);
    $request = new Request;
    $request->setArguments(['slug' => 'nope']);

    $payload = decodeResource($resource->handle($request));

    expect($payload['data']['process'])->toBeNull();
    expect($payload['data']['error'])->toContain("'nope'");
});

it('RoutesResource returns the registered route table', function (): void {
    $payload = decodeResource(app(RoutesResource::class)->handle(new Request));

    expect($payload['data']['count'])->toBe(1);
    expect($payload['data']['routes'][0]['controller_class'])->toBe('App\\Http\\Controllers\\LeadController');
});

it('ClassesResource groups by type', function (): void {
    $payload = decodeResource(app(ClassesResource::class)->handle(new Request));

    expect($payload['data']['classes_by_type'])->toHaveKeys(['model', 'controller']);
    expect($payload['data']['totals']['model'])->toBe(1);
});

it('RisksResource and RecommendationsResource return consolidated entries', function (): void {
    $risks = decodeResource(app(RisksResource::class)->handle(new Request));
    $recs = decodeResource(app(RecommendationsResource::class)->handle(new Request));

    expect($risks['data']['count'])->toBe(1);
    expect($risks['data']['risks'][0]['process'])->toBe('Lead Management');

    expect($recs['data']['count'])->toBe(1);
    expect($recs['data']['recommendations'][0]['message'])->toBe('Add policy.');
});

it('all resources return error envelope when MCP is disabled', function (): void {
    config()->set('process-map.mcp.enabled', false);

    $payload = decodeResource(app(SummaryResource::class)->handle(new Request));

    expect($payload['status'])->toBe('error');
    expect($payload['error']['code'])->toBe('PROCESS_MAP_ERROR');
    expect($payload['error']['message'])->toContain('disabled');
});
