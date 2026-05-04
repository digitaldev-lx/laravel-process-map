<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Commands;

use Illuminate\Console\Command;

final class McpInstallCommand extends Command
{
    protected $signature = 'process-map:mcp-install
        {--enable : Print the env line you need to flip to activate MCP}
        {--show-config : Print the routes/ai.php snippet}';

    protected $description = 'Install instructions for the Laravel Process Map MCP layer (read-only).';

    public function handle(): int
    {
        $handle = (string) config('process-map.mcp.registration.handle', 'process-map');
        $transport = (string) config('process-map.mcp.registration.transport', 'local');
        $webPath = (string) config('process-map.mcp.registration.web_path', '/mcp/process-map');

        $this->info('Laravel Process Map — MCP layer');
        $this->newLine();
        $this->line('What it is:');
        $this->line('  A read-only MCP server (laravel/mcp) that exposes your process map');
        $this->line('  as resources, tools and prompts. Useful for Claude Code and other');
        $this->line('  MCP-compatible clients to query your application architecture.');
        $this->newLine();

        $this->line('Security envelope (always on):');
        $this->line('  - Read-only by design — no shell, no SQL, no HTTP, no .env exposure.');
        $this->line('  - Off by default. Opt in via PROCESS_MAP_MCP_ENABLED=true.');
        $this->line('  - Sensitive metadata keys (password/secret/token/...) are redacted.');
        $this->newLine();

        $this->line('Step 1 — publish the config (if you have not already):');
        $this->line('  php artisan vendor:publish --tag=process-map-config');
        $this->newLine();

        if ($this->option('enable')) {
            $this->line('Step 2 — enable the layer:');
            $this->line('  Add the following to your .env (or set it in your environment):');
            $this->line('');
            $this->line('    PROCESS_MAP_MCP_ENABLED=true');
            $this->line('');
            $this->warn('  This command does not edit .env automatically. Edit it yourself.');
            $this->newLine();
        }

        $this->line('Step 3 — register the server in routes/ai.php:');
        $this->line('  (laravel/mcp loads routes/ai.php automatically once you publish them');
        $this->line('  with `php artisan vendor:publish --tag=ai-routes`.)');
        $this->line('');

        if ($transport === 'web') {
            $this->line("    Mcp::web('{$webPath}', \\DigitaldevLx\\LaravelProcessMap\\Mcp\\Servers\\ProcessMapServer::class)");
            $this->line("        ->middleware(['throttle:mcp']);");
        } else {
            $this->line("    Mcp::local('{$handle}', \\DigitaldevLx\\LaravelProcessMap\\Mcp\\Servers\\ProcessMapServer::class);");
        }

        $this->newLine();
        $this->line('Step 4 — produce a process map (the MCP layer reads it):');
        $this->line('  php artisan process-map:scan --all');
        $this->newLine();

        $this->line('Step 5 — verify:');
        $this->line('  php artisan process-map:mcp-status');
        $this->newLine();

        if ($this->option('show-config')) {
            $this->line('Current MCP config:');
            $this->line(json_encode(
                config('process-map.mcp'),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            ) ?: '{}');
        }

        return self::SUCCESS;
    }
}
