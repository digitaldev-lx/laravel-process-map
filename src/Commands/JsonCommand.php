<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Commands;

use DigitaldevLx\LaravelProcessMap\ProcessMap;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

final class JsonCommand extends Command
{
    protected $signature = 'process-map:json {--output= : Override the output directory}';

    protected $description = 'Generate the JSON process-map artefact';

    public function handle(ProcessMap $processMap, Repository $config): int
    {
        $this->info('Scanning application for the JSON export...');

        $result = $processMap->scan();

        $dir = $this->option('output') ?? $config->get('process-map.output_path', storage_path('app/process-map'));
        $path = rtrim((string) $dir, '/').'/process-map.json';

        $processMap->exportJson($result, $path);

        $this->info('JSON written to: '.$path);

        return self::SUCCESS;
    }
}
