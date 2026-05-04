<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use DigitaldevLx\LaravelProcessMap\Mcp\Support\McpSecurityGuard;
use Illuminate\Config\Repository as ConfigRepository;

function guardWith(array $mcp): McpSecurityGuard
{
    return new McpSecurityGuard(new ConfigRepository([
        'process-map' => ['mcp' => array_replace_recursive([
            'enabled' => true,
            'read_only' => true,
            'tools' => [
                'allow_refresh_scan' => true,
                'allow_compare_scans' => false,
                'max_processes_returned' => 100,
                'max_classes_returned' => 250,
                'max_routes_returned' => 500,
                'max_related_depth' => 3,
                'max_response_bytes' => 256_000,
            ],
            'security' => [
                'allow_external_http' => false,
                'allow_shell_execution' => false,
                'allow_database_queries' => false,
                'allow_code_modification' => false,
            ],
        ], $mcp)],
    ]));
}

it('throws when MCP is disabled and any guarded action is requested', function (): void {
    $guard = guardWith(['enabled' => false]);

    expect(fn () => $guard->ensureMcpEnabled())->toThrow(ProcessMapException::class, 'MCP layer is disabled');
    expect(fn () => $guard->ensureRefreshAllowed())->toThrow(ProcessMapException::class);
});

it('refuses refresh when allow_refresh_scan is false', function (): void {
    $guard = guardWith(['tools' => ['allow_refresh_scan' => false]]);

    expect(fn () => $guard->ensureRefreshAllowed())
        ->toThrow(ProcessMapException::class, 'refresh_process_map');
});

it('refuses compare scans by default', function (): void {
    expect(fn () => guardWith([])->ensureCompareAllowed())
        ->toThrow(ProcessMapException::class, 'compare_process_maps');
});

it('clamps max_related_depth between 1 and 5', function (): void {
    expect(guardWith(['tools' => ['max_related_depth' => 0]])->maxRelatedDepth())->toBe(1);
    expect(guardWith(['tools' => ['max_related_depth' => 99]])->maxRelatedDepth())->toBe(5);
    expect(guardWith(['tools' => ['max_related_depth' => 2]])->maxRelatedDepth())->toBe(2);
});

it('exposes the security policy flags as a flat array', function (): void {
    $flags = guardWith([])->policyFlags();

    expect($flags)->toMatchArray([
        'allow_external_http' => false,
        'allow_shell_execution' => false,
        'allow_database_queries' => false,
        'allow_code_modification' => false,
    ]);
});
