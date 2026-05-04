<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Prompts;

use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompts\Argument;

#[Title('Find automation opportunities')]
#[Description('Has the LLM scan the process map for ways to automate manual or semi-manual workflows.')]
final class FindAutomationOpportunitiesPrompt extends AbstractProcessMapPrompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(name: 'scope', description: 'all | <process-slug>.', required: false),
            new Argument(name: 'priority', description: 'business-impact | technical-effort | balanced.', required: false),
        ];
    }

    /**
     * @return array<string, scalar>
     */
    protected function defaults(): array
    {
        return ['scope' => 'all', 'priority' => 'business-impact'];
    }

    protected function template(): string
    {
        return <<<'PROMPT'
            Use the Laravel Process Map MCP server to identify automation opportunities.

            Scope: {scope}
            Priority model: {priority}

            Look for:
            - manual Artisan commands that could be scheduled;
            - missing scheduled tasks for recurring work;
            - synchronous notifications that should be queued;
            - repeated controller logic that should become an action or service;
            - missing events for cross-cutting concerns;
            - missing jobs for slow operations;
            - missing follow-up processes after key transitions;
            - missing status tracking;
            - missing approvals;
            - processes with low automation level;
            - processes with high business relevance.

            Return:
            1. Top automation opportunities (ranked by the priority model above)
            2. Business impact
            3. Technical effort
            4. Suggested implementation approach
            5. Risks
            6. Suggested Laravel components to use
            PROMPT;
    }
}
