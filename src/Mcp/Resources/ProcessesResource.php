<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;

#[Uri('process-map://processes')]
#[Title('Detected Processes')]
#[Description('Compact list of every detected business process with slug, entity, automation level and counts.')]
final class ProcessesResource extends AbstractProcessMapResource
{
    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        $processes = [];

        foreach (array_slice($this->repository->processes(), 0, $this->guard->maxProcessesReturned()) as $process) {
            $components = is_array($process['components'] ?? null) ? $process['components'] : [];
            $componentsCount = 0;
            foreach ($components as $list) {
                if (is_array($list)) {
                    $componentsCount += count($list);
                }
            }

            $processes[] = [
                'name' => $process['name'] ?? null,
                'slug' => $process['slug'] ?? null,
                'entity' => $process['entity'] ?? null,
                'automation_level' => $process['automation_level'] ?? null,
                'components_count' => $componentsCount,
                'risks_count' => count((array) ($process['risks'] ?? [])),
                'recommendations_count' => count((array) ($process['recommendations'] ?? [])),
                'bottlenecks_count' => count((array) ($process['potential_bottlenecks'] ?? [])),
            ];
        }

        return ['processes' => $processes, 'count' => count($processes)];
    }
}
