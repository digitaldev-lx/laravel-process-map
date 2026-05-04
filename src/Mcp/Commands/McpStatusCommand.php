<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Commands;

use DigitaldevLx\LaravelProcessMap\Mcp\Support\McpSecurityGuard;
use DigitaldevLx\LaravelProcessMap\Mcp\Support\ProcessMapRepository;
use Illuminate\Console\Command;
use Throwable;

final class McpStatusCommand extends Command
{
    protected $signature = 'process-map:mcp-status';

    protected $description = 'Diagnostic status for the Laravel Process Map MCP layer.';

    public function handle(McpSecurityGuard $guard, ProcessMapRepository $repo): int
    {
        $enabled = $guard->mcpEnabled();
        $readOnly = $guard->readOnly();

        $this->line('Laravel Process Map MCP Status');
        $this->newLine();

        $rows = [
            ['Enabled', $enabled ? 'yes' : 'no'],
            ['Mode', $readOnly ? 'read-only' : 'NOT read-only (unsupported)'],
            ['Resources', config('process-map.mcp.resources.enabled', true) ? 'enabled' : 'disabled'],
            ['Tools', config('process-map.mcp.tools.enabled', true) ? 'enabled' : 'disabled'],
            ['Prompts', config('process-map.mcp.prompts.enabled', true) ? 'enabled' : 'disabled'],
            ['JSON path', $repo->path()],
            ['Markdown path', $repo->markdownPath()],
            ['Mermaid path', $repo->mermaidPath()],
        ];

        if ($repo->exists()) {
            try {
                $summary = $repo->summary();
                $rows[] = ['Last scan', (string) ($summary['generated_at'] ?? '—')];
                $rows[] = ['Schema version', (string) ($summary['schema_version'] ?? '—')];
                $rows[] = ['Package version', (string) ($summary['package_version'] ?? '—')];
            } catch (Throwable $e) {
                $rows[] = ['Last scan', 'unreadable: '.$e->getMessage()];
            }
        } else {
            $rows[] = ['Last scan', 'never (run: php artisan process-map:scan --all)'];
        }

        $this->table(['Setting', 'Value'], $rows);

        $this->newLine();
        $this->line('Security policy (cannot be relaxed):');
        foreach ($guard->policyFlags() as $flag => $value) {
            $this->line('  - '.$flag.': '.($value ? 'ALLOWED' : 'disabled'));
        }

        if (! $enabled) {
            $this->newLine();
            $this->warn('MCP is currently disabled. Set PROCESS_MAP_MCP_ENABLED=true to enable it.');
        }

        return self::SUCCESS;
    }
}
