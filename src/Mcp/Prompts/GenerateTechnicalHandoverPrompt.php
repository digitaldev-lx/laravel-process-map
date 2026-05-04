<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Prompts;

use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompts\Argument;

#[Title('Generate a technical handover')]
#[Description('Produces a handover document for an incoming Laravel developer or technical owner.')]
final class GenerateTechnicalHandoverPrompt extends AbstractProcessMapPrompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(name: 'detail_level', description: 'concise | standard | exhaustive.', required: false),
        ];
    }

    /**
     * @return array<string, scalar>
     */
    protected function defaults(): array
    {
        return ['detail_level' => 'standard'];
    }

    protected function template(): string
    {
        return <<<'PROMPT'
            Generate a technical handover document for this Laravel application using the Process Map MCP server.

            Detail level: {detail_level}.

            Use `get_process_map_summary`, `list_processes`, `get_route_map`, and per-process tools to gather the data.

            Include:
            - application overview (one-paragraph elevator pitch);
            - main detected processes;
            - critical routes (auth-protected, destructive verbs);
            - important models;
            - background jobs and their queues;
            - events / listeners and ordering hints;
            - notifications and channels;
            - scheduled tasks;
            - authorisation / policies;
            - risks;
            - operational recommendations;
            - onboarding notes for a new Laravel developer (where to start, what to read first, who to ask).
            PROMPT;
    }
}
