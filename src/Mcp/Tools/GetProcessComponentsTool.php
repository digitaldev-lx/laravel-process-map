<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Get process components')]
#[Description('Returns the FQCNs of components attached to a process, optionally filtered by type (controllers, actions, jobs, ...).')]
#[IsReadOnly]
final class GetProcessComponentsTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'process' => $schema->string()
                ->description('Process slug, name or entity.')
                ->required(),
            'types' => $schema->array()
                ->description('Restrict to these component buckets (e.g. ["actions","jobs"]).'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $needle = (string) $request->get('process', '');
        $process = $this->repository->findProcess($needle);

        if ($process === null) {
            $this->processNotFound($needle);
        }

        $components = is_array($process['components'] ?? null) ? $process['components'] : [];

        $types = $request->get('types');
        if (is_array($types) && $types !== []) {
            $types = array_filter($types, 'is_string');
            $components = array_intersect_key($components, array_flip($types));
        }

        return [
            'process' => $process['name'] ?? null,
            'slug' => $process['slug'] ?? null,
            'components' => $components,
        ];
    }
}
