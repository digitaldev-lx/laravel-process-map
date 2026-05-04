<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap;

use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Exporters\JsonExporter;
use DigitaldevLx\LaravelProcessMap\Exporters\MarkdownExporter;
use DigitaldevLx\LaravelProcessMap\Exporters\MermaidExporter;
use DigitaldevLx\LaravelProcessMap\Scanners\ApplicationScanner;

/**
 * Public entry point. Resolved as a singleton through the container; userland
 * code and Artisan commands are expected to depend on this class rather than
 * on the underlying scanners and exporters.
 */
class ProcessMap
{
    public function __construct(
        private readonly ApplicationScanner $scanner,
        private readonly JsonExporter $jsonExporter,
        private readonly MarkdownExporter $markdownExporter,
        private readonly MermaidExporter $mermaidExporter,
    ) {}

    public function version(): string
    {
        return ApplicationScanner::packageVersion();
    }

    public function scan(): ProcessMapResult
    {
        return $this->scanner->scan();
    }

    public function exportJson(ProcessMapResult $result, ?string $path = null): string
    {
        return $this->jsonExporter->export($result, $path);
    }

    public function exportMarkdown(ProcessMapResult $result, ?string $path = null): string
    {
        return $this->markdownExporter->export($result, $path);
    }

    public function exportMermaid(ProcessMapResult $result, ?string $path = null): string
    {
        return $this->mermaidExporter->export($result, $path);
    }
}
