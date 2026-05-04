<?php

declare(strict_types=1);

it('process-map:mcp-install prints the security envelope and registration snippet', function (): void {
    $this->artisan('process-map:mcp-install')
        ->assertExitCode(0)
        ->expectsOutputToContain('Laravel Process Map — MCP layer')
        ->expectsOutputToContain('Read-only by design')
        ->expectsOutputToContain("Mcp::local('process-map'");
});

it('process-map:mcp-install --enable hints at the env line without editing it', function (): void {
    $this->artisan('process-map:mcp-install', ['--enable' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain('PROCESS_MAP_MCP_ENABLED=true')
        ->expectsOutputToContain('does not edit .env automatically');
});

it('process-map:mcp-status reports disabled by default', function (): void {
    config()->set('process-map.mcp.enabled', false);

    $this->artisan('process-map:mcp-status')
        ->assertExitCode(0)
        ->expectsOutputToContain('Laravel Process Map MCP Status')
        ->expectsOutputToContain('MCP is currently disabled');
});

it('process-map:mcp-status reports enabled when configured and flags the security policy', function (): void {
    config()->set('process-map.mcp.enabled', true);

    $this->artisan('process-map:mcp-status')
        ->assertExitCode(0)
        ->expectsOutputToContain('allow_external_http: disabled')
        ->expectsOutputToContain('allow_shell_execution: disabled')
        ->expectsOutputToContain('allow_database_queries: disabled')
        ->expectsOutputToContain('allow_code_modification: disabled');
});
