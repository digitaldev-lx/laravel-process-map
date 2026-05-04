<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Prompts;

use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompts\Argument;

#[Title('Plan a safe refactoring')]
#[Description('Forces the LLM to produce a low-risk refactoring plan for a process before changing any code.')]
final class RefactorProcessSafelyPrompt extends AbstractProcessMapPrompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(name: 'process', description: 'Process slug, name or entity.', required: true),
            new Argument(name: 'target_style', description: 'Architectural target (e.g. actions, services, modules).', required: false),
            new Argument(name: 'risk_tolerance', description: 'low, medium, high.', required: false),
        ];
    }

    /**
     * @return array<string, scalar>
     */
    protected function defaults(): array
    {
        return ['target_style' => 'actions', 'risk_tolerance' => 'low'];
    }

    protected function template(): string
    {
        return <<<'PROMPT'
            You are preparing a SAFE refactoring plan for the Laravel process **{process}**.

            Use the Laravel Process Map MCP server to inspect every related component before proposing changes. Prefer `get_process_details`, `get_process_components`, `get_related_classes` and `get_mermaid_diagram`.

            Target architectural style: {target_style}.
            Risk tolerance: {risk_tolerance}.

            Constraints:
            - Do not change behaviour without an explicit reason.
            - Preserve public API and route behaviour.
            - Prefer small commits.
            - Prefer adding tests BEFORE refactoring.
            - Avoid destructive changes.
            - Identify a rollback strategy.
            - Respect existing architecture and naming conventions.

            Return:
            1. Current process structure (1-paragraph summary)
            2. Refactoring goal
            3. Files likely affected (with paths from the map)
            4. Step-by-step refactoring plan
            5. Tests to add or update
            6. Risks and mitigations
            7. Rollback plan
            PROMPT;
    }
}
