<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\AuditProcessPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\ProcessesResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Servers\ProcessMapServer;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetProcessMapSummaryTool;

beforeEach(function (): void {
    $this->tmp = sys_get_temp_dir().'/process-map-mcp-server-'.uniqid();
    mkdir($this->tmp, 0777, true);

    file_put_contents($this->tmp.'/process-map.json', json_encode([
        'schema_version' => '0.1',
        'app' => ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        'summary' => ['models' => 1, 'processes' => 1],
        'processes' => [[
            'name' => 'Lead Management',
            'slug' => 'lead-management',
            'entity' => 'Lead',
            'automation_level' => 'medium',
            'components' => ['models' => ['App\\Models\\Lead']],
            'risks' => [],
            'recommendations' => [],
            'potential_bottlenecks' => [],
        ]],
        'classes' => [],
        'routes' => [],
    ]));

    config()->set('process-map.output_path', $this->tmp);
    config()->set('process-map.mcp.enabled', true);
    config()->set('process-map.mcp.cache.enabled', false);
});

afterEach(function (): void {
    exec('rm -rf '.escapeshellarg($this->tmp));
});

it('declares 11 tools, 8 resources and 6 prompts', function (): void {
    $server = new ReflectionClass(ProcessMapServer::class);

    $tools = $server->getProperty('tools')->getDefaultValue();
    $resources = $server->getProperty('resources')->getDefaultValue();
    $prompts = $server->getProperty('prompts')->getDefaultValue();

    expect($tools)->toHaveCount(11);
    expect($resources)->toHaveCount(8);
    expect($prompts)->toHaveCount(6);
});

it('routes a tool call through the laravel/mcp testing harness', function (): void {
    $response = ProcessMapServer::tool(GetProcessMapSummaryTool::class);

    $response->assertOk()
        ->assertSee('"app"')
        ->assertSee('Demo');
});

it('routes a resource read through the laravel/mcp testing harness', function (): void {
    $response = ProcessMapServer::resource(ProcessesResource::class);

    $response->assertOk()->assertSee('lead-management');
});

it('routes a prompt request through the laravel/mcp testing harness', function (): void {
    $response = ProcessMapServer::prompt(
        AuditProcessPrompt::class,
        ['process' => 'lead-management'],
    );

    $response->assertOk();
});
