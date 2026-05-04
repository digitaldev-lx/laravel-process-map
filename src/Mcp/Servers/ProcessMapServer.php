<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Servers;

use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\AuditProcessPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\DocumentProcessPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\FindAutomationOpportunitiesPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\GenerateTechnicalHandoverPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\PrepareMcpToolsFromActionsPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\RefactorProcessSafelyPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\ClassesResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\MermaidResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\ProcessesResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\ProcessResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\RecommendationsResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\RisksResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\RoutesResource;
use DigitaldevLx\LaravelProcessMap\Mcp\Resources\SummaryResource;
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
use DigitaldevLx\LaravelProcessMap\Mcp\Tools\RefreshProcessMapTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Laravel Process Map')]
#[Version('1.1.0')]
#[Description('Read-only MCP interface to a Laravel application\'s process map.')]
#[Instructions(<<<'INSTRUCTIONS'
This MCP server exposes the static process-map of a Laravel application as
read-only resources, tools and prompts. It never executes business code,
queries the database, makes HTTP calls, or modifies the application.

Start with the `process-map://summary` resource or the `get_process_map_summary`
tool, then drill into specific processes with `get_process_details` /
`get_process_components`. Use the prompts (audit_process, refactor_process_safely,
document_process, ...) for opinionated workflows.
INSTRUCTIONS)]
final class ProcessMapServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        GetProcessMapSummaryTool::class,
        ListProcessesTool::class,
        GetProcessDetailsTool::class,
        GetProcessComponentsTool::class,
        GetProcessRisksTool::class,
        GetProcessRecommendationsTool::class,
        GetRelatedClassesTool::class,
        GetRouteMapTool::class,
        GetMermaidDiagramTool::class,
        RefreshProcessMapTool::class,
        CompareProcessMapsTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        SummaryResource::class,
        ProcessesResource::class,
        ProcessResource::class,
        RoutesResource::class,
        ClassesResource::class,
        RisksResource::class,
        RecommendationsResource::class,
        MermaidResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        AuditProcessPrompt::class,
        RefactorProcessSafelyPrompt::class,
        DocumentProcessPrompt::class,
        FindAutomationOpportunitiesPrompt::class,
        GenerateTechnicalHandoverPrompt::class,
        PrepareMcpToolsFromActionsPrompt::class,
    ];
}
