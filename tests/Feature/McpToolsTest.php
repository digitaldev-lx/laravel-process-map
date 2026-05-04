<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Mcp\Tools\CompareProcessMapsTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetMermaidDiagramTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetProcessComponentsTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetProcessDetailsTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetProcessMapSummaryTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetProcessRecommendationsTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetProcessRisksTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetRelatedClassesTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\GetRouteMapTool;
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\ListProcessesTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function (): void {
    $this->tmp = sys_get_temp_dir().'/process-map-mcp-tools-'.uniqid();
    mkdir($this->tmp, 0777, true);

    file_put_contents($this->tmp.'/process-map.json', json_encode([
        'schema_version' => '0.1',
        'processes' => [[
            'name' => 'Lead Management',
            'slug' => 'lead-management',
            'entity' => 'Lead',
            'automation_level' => 'medium',
            'components' => [
                'controllers' => ['App\\Http\\Controllers\\LeadController'],
                'actions' => ['App\\Actions\\CreateLeadAction'],
                'jobs' => ['App\\Jobs\\SendLeadFollowUpJob'],
            ],
            'risks' => ['Missing policy.'],
            'recommendations' => ['Add policy.'],
            'potential_bottlenecks' => ['Sync notifications.'],
        ]],
        'classes' => [
            ['type' => 'controller', 'class_name' => 'App\\Http\\Controllers\\LeadController', 'short_name' => 'LeadController', 'file_path' => 'app/Http/Controllers/LeadController.php', 'references' => ['App\\Actions\\CreateLeadAction']],
            ['type' => 'action', 'class_name' => 'App\\Actions\\CreateLeadAction', 'short_name' => 'CreateLeadAction', 'file_path' => 'app/Actions/CreateLeadAction.php', 'references' => ['App\\Jobs\\SendLeadFollowUpJob']],
            ['type' => 'job', 'class_name' => 'App\\Jobs\\SendLeadFollowUpJob', 'short_name' => 'SendLeadFollowUpJob', 'file_path' => 'app/Jobs/SendLeadFollowUpJob.php', 'references' => []],
        ],
        'routes' => [
            ['methods' => ['POST'], 'uri' => 'leads', 'name' => 'leads.store', 'controller_class' => 'App\\Http\\Controllers\\LeadController', 'controller_method' => 'store', 'middleware' => ['web', 'auth']],
            ['methods' => ['GET'], 'uri' => 'health', 'name' => 'health', 'controller_class' => null, 'controller_method' => null, 'middleware' => []],
        ],
        'app' => ['name' => 'Demo', 'environment' => 'testing', 'laravel_version' => '13', 'php_version' => '8.4'],
        'summary' => ['models' => 0, 'controllers' => 1, 'processes' => 1],
    ]));

    file_put_contents($this->tmp.'/process-map.mmd', "flowchart TD\n%% Process: Lead Management\nflowchart TD\n  Lead --> Action\n");

    config()->set('process-map.output_path', $this->tmp);
    config()->set('process-map.mcp.enabled', true);
    config()->set('process-map.mcp.cache.enabled', false);
});

afterEach(function (): void {
    exec('rm -rf '.escapeshellarg($this->tmp));
});

function decodeTool(Response $response): array
{
    return json_decode((string) $response->content(), true) ?? [];
}

it('GetProcessMapSummaryTool returns the summary envelope', function (): void {
    $payload = decodeTool(app(GetProcessMapSummaryTool::class)->handle(new Request));

    expect($payload['status'])->toBe('ok');
    expect($payload['data']['app']['name'])->toBe('Demo');
});

it('ListProcessesTool returns counts and respects automation_level filter', function (): void {
    $tool = app(ListProcessesTool::class);

    $all = decodeTool($tool->handle(new Request));
    expect($all['data']['count'])->toBe(1);

    $request = new Request;
    $request->setArguments(['automation_level' => 'high']);
    $filtered = decodeTool($tool->handle($request));
    expect($filtered['data']['count'])->toBe(0);
});

it('GetProcessDetailsTool finds a process by slug', function (): void {
    $request = new Request;
    $request->setArguments(['process' => 'lead-management']);

    $payload = decodeTool(app(GetProcessDetailsTool::class)->handle($request));

    expect($payload['status'])->toBe('ok');
    expect($payload['data']['process']['name'])->toBe('Lead Management');
});

it('GetProcessDetailsTool returns process_not_found error for unknown needle', function (): void {
    $request = new Request;
    $request->setArguments(['process' => 'nope']);

    $payload = decodeTool(app(GetProcessDetailsTool::class)->handle($request));

    expect($payload['status'])->toBe('error');
    expect($payload['error']['message'])->toContain("'nope'");
});

it('GetProcessComponentsTool filters by type list', function (): void {
    $request = new Request;
    $request->setArguments(['process' => 'lead-management', 'types' => ['actions', 'jobs']]);

    $payload = decodeTool(app(GetProcessComponentsTool::class)->handle($request));

    expect($payload['data']['components'])->toHaveKeys(['actions', 'jobs']);
    expect($payload['data']['components'])->not->toHaveKey('controllers');
});

it('GetProcessRisksTool and GetProcessRecommendationsTool return their slices', function (): void {
    $request = new Request;
    $request->setArguments(['process' => 'lead-management']);

    $risks = decodeTool(app(GetProcessRisksTool::class)->handle($request));
    $recs = decodeTool(app(GetProcessRecommendationsTool::class)->handle($request));

    expect($risks['data']['risks'])->toContain('Missing policy.');
    expect($risks['data']['potential_bottlenecks'])->toContain('Sync notifications.');
    expect($recs['data']['recommendations'])->toContain('Add policy.');
});

it('GetRelatedClassesTool walks references at the configured depth', function (): void {
    $request = new Request;
    $request->setArguments(['class' => 'App\\Http\\Controllers\\LeadController', 'depth' => 2]);

    $payload = decodeTool(app(GetRelatedClassesTool::class)->handle($request));

    $names = array_column($payload['data']['related'], 'class');

    expect($names)->toContain('App\\Actions\\CreateLeadAction');
    expect($names)->toContain('App\\Jobs\\SendLeadFollowUpJob');
});

it('GetRouteMapTool filters by method and middleware', function (): void {
    $tool = app(GetRouteMapTool::class);

    $request = new Request;
    $request->setArguments(['method' => 'post']);
    $post = decodeTool($tool->handle($request));
    expect($post['data']['count'])->toBe(1);

    $request = new Request;
    $request->setArguments(['middleware' => 'auth']);
    $auth = decodeTool($tool->handle($request));
    expect($auth['data']['count'])->toBe(1);
});

it('GetRouteMapTool filters by process slug', function (): void {
    $request = new Request;
    $request->setArguments(['process' => 'lead-management']);

    $payload = decodeTool(app(GetRouteMapTool::class)->handle($request));

    expect($payload['data']['count'])->toBe(1);
    expect($payload['data']['routes'][0]['controller_class'])->toBe('App\\Http\\Controllers\\LeadController');
});

it('GetMermaidDiagramTool returns the overview when no process is supplied', function (): void {
    $payload = decodeTool(app(GetMermaidDiagramTool::class)->handle(new Request));

    expect($payload['data']['scope'])->toBe('overview');
    expect($payload['data']['mermaid'])->toContain('flowchart TD');
});

it('GetMermaidDiagramTool slices the per-process block when requested', function (): void {
    $request = new Request;
    $request->setArguments(['process' => 'lead-management']);

    $payload = decodeTool(app(GetMermaidDiagramTool::class)->handle($request));

    expect($payload['data']['scope'])->toBe('process');
    expect($payload['data']['mermaid'])->toContain('Lead Management');
});

it('CompareProcessMapsTool refuses by default and quotes the gating flag', function (): void {
    $payload = decodeTool(app(CompareProcessMapsTool::class)->handle(new Request));

    expect($payload['status'])->toBe('error');
    expect($payload['error']['message'])->toContain('compare_process_maps');
});

it('every tool returns the standard error envelope when MCP is disabled', function (): void {
    config()->set('process-map.mcp.enabled', false);

    $payload = decodeTool(app(GetProcessMapSummaryTool::class)->handle(new Request));

    expect($payload['status'])->toBe('error');
    expect($payload['error']['message'])->toContain('disabled');
});
