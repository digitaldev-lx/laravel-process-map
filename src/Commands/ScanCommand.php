<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Commands;

use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\ProcessMap;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

final class ScanCommand extends Command
{
    protected $signature = 'process-map:scan
        {--json : Generate the JSON export}
        {--markdown : Generate the Markdown report}
        {--mermaid : Generate the Mermaid diagram}
        {--all : Generate every default export}
        {--output= : Override the output directory for this run}
        {--no-routes : Skip the route scanner}
        {--no-process-detection : Skip the heuristic process detection}';

    protected $description = 'Scan the application and export the discovered process map';

    public function handle(ProcessMap $processMap, Repository $config): int
    {
        if ($this->option('no-routes')) {
            $config->set('process-map.scan.routes', false);
        }

        if ($this->option('no-process-detection')) {
            $config->set('process-map.process_detection.enabled', false);
        }

        $this->info('Scanning application...');

        $result = $processMap->scan();

        $this->renderSummary($result);

        $outputDir = $this->resolveOutputDir($config);
        $formats = $this->formats($config);
        $written = [];

        if ($formats['json']) {
            $written[] = $this->writeJson($processMap, $result, $outputDir);
        }

        if ($formats['markdown']) {
            $written[] = $this->writeMarkdown($processMap, $result, $outputDir);
        }

        if ($formats['mermaid']) {
            $written[] = $this->writeMermaid($processMap, $result, $outputDir);
        }

        if ($written !== []) {
            $this->newLine();
            $this->line('Outputs:');

            foreach ($written as $path) {
                $this->line('  - '.$path);
            }
        } else {
            $this->warn('No exports requested. Pass --json, --markdown, --mermaid or --all.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{json: bool, markdown: bool, mermaid: bool}
     */
    private function formats(Repository $config): array
    {
        if ($this->option('all')) {
            return ['json' => true, 'markdown' => true, 'mermaid' => true];
        }

        $any = $this->option('json') || $this->option('markdown') || $this->option('mermaid');

        if (! $any) {
            return [
                'json' => (bool) $config->get('process-map.exports.json', true),
                'markdown' => (bool) $config->get('process-map.exports.markdown', true),
                'mermaid' => (bool) $config->get('process-map.exports.mermaid', true),
            ];
        }

        return [
            'json' => (bool) $this->option('json'),
            'markdown' => (bool) $this->option('markdown'),
            'mermaid' => (bool) $this->option('mermaid'),
        ];
    }

    private function resolveOutputDir(Repository $config): string
    {
        $override = $this->option('output');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return (string) $config->get('process-map.output_path', storage_path('app/process-map'));
    }

    private function writeJson(ProcessMap $processMap, ProcessMapResult $result, string $dir): string
    {
        $path = rtrim($dir, '/').'/process-map.json';
        $processMap->exportJson($result, $path);

        return $path;
    }

    private function writeMarkdown(ProcessMap $processMap, ProcessMapResult $result, string $dir): string
    {
        $path = rtrim($dir, '/').'/process-map.md';
        $processMap->exportMarkdown($result, $path);

        return $path;
    }

    private function writeMermaid(ProcessMap $processMap, ProcessMapResult $result, string $dir): string
    {
        $path = rtrim($dir, '/').'/process-map.mmd';
        $processMap->exportMermaid($result, $path);

        return $path;
    }

    private function renderSummary(ProcessMapResult $result): void
    {
        $this->newLine();
        $this->info('Scanned:');
        $this->line('  - Models: '.$result->summary->models);
        $this->line('  - Controllers: '.$result->summary->controllers);
        $this->line('  - Actions: '.$result->summary->actions);
        $this->line('  - Jobs: '.$result->summary->jobs);
        $this->line('  - Events: '.$result->summary->events);
        $this->line('  - Listeners: '.$result->summary->listeners);
        $this->line('  - Notifications: '.$result->summary->notifications);
        $this->line('  - Policies: '.$result->summary->policies);
        $this->line('  - Commands: '.$result->summary->commands);
        $this->line('  - Routes: '.$result->summary->routes);

        if ($result->processes !== []) {
            $this->newLine();
            $this->info('Detected processes:');

            foreach ($result->processes as $process) {
                $this->line(sprintf('  - %s (automation: %s)', $process->name, $process->automationLevel->value));
            }
        }
    }
}
