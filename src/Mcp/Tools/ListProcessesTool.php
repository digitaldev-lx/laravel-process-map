<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('List processes')]
#[Description('Returns the compact list of detected business processes with slug, entity, automation level and counts.')]
#[IsReadOnly]
final class ListProcessesTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'automation_level' => $schema->string()
                ->description('Filter by automation level: none, low, medium, high.')
                ->enum(['none', 'low', 'medium', 'high']),
            'include_counts' => $schema->boolean()
                ->description('Include component, risk and recommendation counts.')
                ->default(true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $level = $request->get('automation_level');
        $includeCounts = (bool) $request->get('include_counts', true);

        $processes = $this->repository->processes();

        if (is_string($level) && $level !== '') {
            $processes = array_values(array_filter(
                $processes,
                static fn (array $p): bool => ($p['automation_level'] ?? null) === $level,
            ));
        }

        $processes = array_slice($processes, 0, $this->guard->maxProcessesReturned());

        $compact = [];

        foreach ($processes as $process) {
            $entry = [
                'name' => $process['name'] ?? null,
                'slug' => $process['slug'] ?? null,
                'entity' => $process['entity'] ?? null,
                'automation_level' => $process['automation_level'] ?? null,
            ];

            if ($includeCounts) {
                $components = is_array($process['components'] ?? null) ? $process['components'] : [];
                $componentsCount = 0;
                foreach ($components as $list) {
                    if (is_array($list)) {
                        $componentsCount += count($list);
                    }
                }
                $entry['components_count'] = $componentsCount;
                $entry['risks_count'] = count((array) ($process['risks'] ?? []));
                $entry['recommendations_count'] = count((array) ($process['recommendations'] ?? []));
                $entry['bottlenecks_count'] = count((array) ($process['potential_bottlenecks'] ?? []));
            }

            $compact[] = $entry;
        }

        return ['processes' => $compact, 'count' => count($compact)];
    }
}
