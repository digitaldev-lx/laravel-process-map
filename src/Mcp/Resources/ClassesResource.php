<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;

#[Uri('process-map://classes')]
#[Title('Discovered Classes')]
#[Description('All discovered classes grouped by type (models, controllers, actions, jobs, events, listeners, notifications, policies, commands).')]
final class ClassesResource extends AbstractProcessMapResource
{
    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        $grouped = $this->repository->classesByType();
        $max = $this->guard->maxClassesReturned();
        $perTypeCap = max(1, intdiv($max, max(1, count($grouped) ?: 1)));

        $truncated = false;

        foreach ($grouped as $type => $classes) {
            if (count($classes) > $perTypeCap) {
                $grouped[$type] = array_slice($classes, 0, $perTypeCap);
                $truncated = true;
            }
        }

        return [
            'classes_by_type' => $grouped,
            'totals' => array_map(fn (array $list): int => count($list), $grouped),
            'truncated' => $truncated,
            'per_type_cap' => $perTypeCap,
        ];
    }
}
