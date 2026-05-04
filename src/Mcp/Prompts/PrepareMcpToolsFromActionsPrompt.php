<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Prompts;

use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompts\Argument;

#[Title('Prepare future MCP tools from existing Laravel Actions')]
#[Description('Classifies discovered actions for potential exposure as MCP tools, with explicit safety calls.')]
final class PrepareMcpToolsFromActionsPrompt extends AbstractProcessMapPrompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(name: 'scope', description: 'all | <process-slug>.', required: false),
            new Argument(name: 'safety', description: 'read-only-first | balanced | aggressive.', required: false),
        ];
    }

    /**
     * @return array<string, scalar>
     */
    protected function defaults(): array
    {
        return ['scope' => 'all', 'safety' => 'read-only-first'];
    }

    protected function template(): string
    {
        return <<<'PROMPT'
            Analyse the Laravel Actions discovered by the Process Map and identify which could be safely exposed as MCP tools in the future.

            Scope: {scope}.
            Safety mode: {safety}.

            Classify each action as:
            - **safe read-only candidate** — pure inspection or reporting;
            - **requires human approval** — has side effects but is reversible;
            - **unsafe / destructive** — irreversible or sensitive (deletes, billing, auth changes);
            - **not suitable for MCP** — too coupled to a specific user / session / device.

            For each candidate, include:
            - action class (FQCN);
            - business capability (one short sentence);
            - input data likely required;
            - output expected;
            - authorisation required;
            - safety concerns;
            - recommended MCP exposure strategy (read-only mirror, gated tool, prompt-only, do not expose).

            End with a short summary of how many actions fall into each bucket.
            PROMPT;
    }
}
