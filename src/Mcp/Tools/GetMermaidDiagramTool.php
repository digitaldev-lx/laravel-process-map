<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Get mermaid diagram')]
#[Description('Returns the Mermaid flowchart string. When `process` is supplied, attempts to slice the per-process block; otherwise returns the full diagram.')]
#[IsReadOnly]
final class GetMermaidDiagramTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'process' => $schema->string()->description('Process slug, name or entity.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $path = $this->repository->mermaidPath();

        if (! is_file($path)) {
            return [
                'mermaid' => '',
                'note' => 'No mermaid file. Run `php artisan process-map:scan --mermaid` first.',
            ];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return ['mermaid' => '', 'note' => 'Mermaid file unreadable.'];
        }

        $processNeedle = $request->get('process');

        if (! is_string($processNeedle) || $processNeedle === '') {
            return ['mermaid' => $contents, 'scope' => 'overview'];
        }

        $process = $this->repository->findProcess($processNeedle);

        if ($process === null) {
            $this->processNotFound($processNeedle);
        }

        $name = (string) ($process['name'] ?? '');
        $marker = '%% Process: '.$name;
        $position = strpos($contents, $marker);

        if ($position === false) {
            return [
                'mermaid' => $contents,
                'scope' => 'overview',
                'note' => "Per-process block for '{$name}' not found in the diagram; returning the overview.",
            ];
        }

        $remainder = substr($contents, $position);
        $nextProcess = strpos($remainder, '%% Process: ', strlen($marker));
        $slice = $nextProcess !== false ? substr($remainder, 0, $nextProcess) : $remainder;

        return [
            'mermaid' => trim($slice),
            'scope' => 'process',
            'process' => $name,
            'slug' => $process['slug'] ?? null,
        ];
    }
}
