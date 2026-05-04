<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Support;

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Single source of truth for the security envelope of the MCP layer.
 *
 * Every tool/resource/prompt asks the guard *before* doing anything
 * that could in theory leak information or trigger work — even though
 * the implementations themselves are already pure. This keeps the
 * read-only invariant visible in code review: searching for
 * `McpSecurityGuard` shows every place where a check happens.
 */
final class McpSecurityGuard
{
    public function __construct(private readonly Config $config) {}

    public function ensureMcpEnabled(): void
    {
        if (! $this->mcpEnabled()) {
            throw new ProcessMapException(
                'MCP layer is disabled. Set PROCESS_MAP_MCP_ENABLED=true in your .env or process-map.mcp.enabled=true in config.'
            );
        }
    }

    public function ensureRefreshAllowed(): void
    {
        $this->ensureMcpEnabled();

        if (! (bool) $this->config->get('process-map.mcp.tools.allow_refresh_scan', true)) {
            throw new ProcessMapException(
                'The `refresh_process_map` tool is disabled by configuration. '.
                'Set process-map.mcp.tools.allow_refresh_scan=true to enable it.'
            );
        }
    }

    public function ensureCompareAllowed(): void
    {
        $this->ensureMcpEnabled();

        if (! (bool) $this->config->get('process-map.mcp.tools.allow_compare_scans', false)) {
            throw new ProcessMapException(
                'The `compare_process_maps` tool is disabled by configuration. '.
                'Compare support has not yet shipped — see the roadmap.'
            );
        }
    }

    public function mcpEnabled(): bool
    {
        return (bool) $this->config->get('process-map.mcp.enabled', false);
    }

    public function readOnly(): bool
    {
        return (bool) $this->config->get('process-map.mcp.read_only', true);
    }

    public function maxProcessesReturned(): int
    {
        return max(1, (int) $this->config->get('process-map.mcp.tools.max_processes_returned', 100));
    }

    public function maxClassesReturned(): int
    {
        return max(1, (int) $this->config->get('process-map.mcp.tools.max_classes_returned', 250));
    }

    public function maxRoutesReturned(): int
    {
        return max(1, (int) $this->config->get('process-map.mcp.tools.max_routes_returned', 500));
    }

    public function maxRelatedDepth(): int
    {
        return max(1, min(5, (int) $this->config->get('process-map.mcp.tools.max_related_depth', 3)));
    }

    public function maxResponseBytes(): int
    {
        return max(1024, (int) $this->config->get('process-map.mcp.tools.max_response_bytes', 256_000));
    }

    /**
     * Flatten the security flag block for diagnostic output.
     *
     * @return array<string, bool>
     */
    public function policyFlags(): array
    {
        return [
            'allow_external_http' => (bool) $this->config->get('process-map.mcp.security.allow_external_http', false),
            'allow_shell_execution' => (bool) $this->config->get('process-map.mcp.security.allow_shell_execution', false),
            'allow_database_queries' => (bool) $this->config->get('process-map.mcp.security.allow_database_queries', false),
            'allow_code_modification' => (bool) $this->config->get('process-map.mcp.security.allow_code_modification', false),
        ];
    }
}
