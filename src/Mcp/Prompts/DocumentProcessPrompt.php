<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Prompts;

use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompts\Argument;

#[Title('Document a process')]
#[Description('Generates a documentation block for a Laravel process targeted at the specified audience.')]
final class DocumentProcessPrompt extends AbstractProcessMapPrompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(name: 'process', description: 'Process slug, name or entity.', required: true),
            new Argument(name: 'audience', description: 'technical | product | onboarding.', required: false),
        ];
    }

    /**
     * @return array<string, scalar>
     */
    protected function defaults(): array
    {
        return ['audience' => 'technical'];
    }

    protected function template(): string
    {
        return <<<'PROMPT'
            Generate documentation for the Laravel process **{process}** using the Process Map MCP server.

            Audience: {audience}.

            Pull the structure from `get_process_details`, components from `get_process_components`, routes from `get_route_map`, and the diagram from `get_mermaid_diagram`. Embed the resulting Mermaid in the document.

            Include:
            - process purpose;
            - entry points (HTTP routes, scheduled tasks, console commands);
            - controllers;
            - actions / services;
            - models;
            - jobs;
            - events / listeners;
            - notifications and channels;
            - policies / authorisation;
            - data flow (in plain prose, then as a Mermaid block);
            - operational notes;
            - known risks;
            - improvement opportunities.
            PROMPT;
    }
}
