<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Prompts;

use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompts\Argument;

#[Title('Audit a process')]
#[Description('Walks the LLM through a structured audit of one Laravel process using the Process Map MCP server.')]
final class AuditProcessPrompt extends AbstractProcessMapPrompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(name: 'process', description: 'Process slug, name or entity to audit.', required: true),
            new Argument(name: 'depth', description: 'Audit depth: quick, standard, deep.', required: false),
        ];
    }

    /**
     * @return array<string, scalar>
     */
    protected function defaults(): array
    {
        return ['depth' => 'standard'];
    }

    protected function template(): string
    {
        return <<<'PROMPT'
            You are auditing a Laravel business process using the Laravel Process Map MCP server.

            Use the available `process-map://...` resources and the read-only tools (`get_process_details`, `get_process_components`, `get_process_risks`, `get_process_recommendations`, `get_route_map`, `get_related_classes`, `get_mermaid_diagram`) to inspect the process **{process}**.

            Audit depth: {depth}.

            Analyse:
            - business responsibility;
            - technical components;
            - controllers / actions / jobs / events / listeners / notifications / policies involved;
            - automation level;
            - bottlenecks;
            - risks;
            - missing tests or missing safeguards;
            - opportunities for simplification;
            - opportunities for automation;
            - safe next steps.

            Return:
            1. Executive summary
            2. Technical findings
            3. Process risks
            4. Automation opportunities
            5. Refactoring recommendations
            6. Suggested test coverage
            7. Questions for the product / business owner
            PROMPT;
    }
}
