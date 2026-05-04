<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Compare process maps')]
#[Description('Compare two process map snapshots (added/removed processes, classes, risks, recommendations). Disabled by default in v1.1.0; gated by process-map.mcp.tools.allow_compare_scans.')]
#[IsReadOnly]
final class CompareProcessMapsTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $this->guard->ensureCompareAllowed();

        // Snapshot diffing requires historic snapshots, which are on the
        // v1.2 roadmap. The tool exists so MCP clients can discover it.
        return [
            'note' => 'Snapshot history is on the v1.2 roadmap. The tool will diff two stored snapshots once that lands.',
        ];
    }
}
