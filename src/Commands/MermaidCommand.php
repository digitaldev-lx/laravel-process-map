<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Commands;

use DigitaldevLx\LaravelProcessMap\ProcessMap;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

final class MermaidCommand extends Command
{
    protected $signature = 'process-map:mermaid {--output= : Override the output directory}';

    protected $description = 'Generate the Mermaid diagram of the process map';

    public function handle(ProcessMap $processMap, Repository $config): int
    {
        $this->info('Scanning application for the Mermaid diagram...');

        $result = $processMap->scan();

        $dir = $this->option('output') ?? $config->get('process-map.output_path', storage_path('app/process-map'));
        $path = rtrim((string) $dir, '/').'/process-map.mmd';

        $processMap->exportMermaid($result, $path);

        $this->info('Mermaid written to: '.$path);

        return self::SUCCESS;
    }
}
