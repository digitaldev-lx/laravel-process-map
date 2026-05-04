<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Commands;

use DigitaldevLx\LaravelProcessMap\ProcessMap;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

final class ReportCommand extends Command
{
    protected $signature = 'process-map:report {--output= : Override the output directory}';

    protected $description = 'Generate the Markdown process-map report';

    public function handle(ProcessMap $processMap, Repository $config): int
    {
        $this->info('Scanning application for the Markdown report...');

        $result = $processMap->scan();

        $dir = $this->option('output') ?? $config->get('process-map.output_path', storage_path('app/process-map'));
        $path = rtrim((string) $dir, '/').'/process-map.md';

        $processMap->exportMarkdown($result, $path);

        $this->info('Report written to: '.$path);

        return self::SUCCESS;
    }
}
