<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

/**
 * Shared boilerplate for every prompt template the MCP layer ships.
 *
 * Each child returns a single user-message Response built from a heredoc
 * template with `{placeholder}` substitution. The replacements are pulled
 * from the request arguments after a default merge.
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractProcessMapPrompt extends Prompt
{
    /**
     * @return array<int, Response|array<int, Response>>
     */
    public function handle(Request $request): array
    {
        $merged = array_merge($this->defaults(), array_filter($request->all(), static fn ($v) => $v !== null && $v !== ''));

        $rendered = $this->render($this->template(), $merged);

        return [Response::text($rendered)];
    }

    /**
     * Heredoc template with `{key}` placeholders.
     */
    abstract protected function template(): string;

    /**
     * @return array<string, scalar|array<int, string>>
     */
    abstract protected function defaults(): array;

    /**
     * @param  array<string, mixed>  $values
     */
    protected function render(string $template, array $values): string
    {
        foreach ($values as $key => $value) {
            $replacement = is_array($value) ? implode(', ', array_filter($value, 'is_scalar')) : (string) $value;
            $template = str_replace('{'.$key.'}', $replacement, $template);
        }

        return $template;
    }
}
