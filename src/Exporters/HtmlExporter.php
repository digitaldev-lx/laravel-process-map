<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Exporters;

use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;

/**
 * Placeholder exporter for v0.1. The full HTML dashboard is on the v0.2
 * roadmap; for now the class throws a clear exception so the rest of the
 * pipeline (config, container bindings, command flags) can still be
 * wired up as if it existed.
 */
final class HtmlExporter extends AbstractExporter
{
    public function export(ProcessMapResult $result, ?string $path = null): string
    {
        throw ProcessMapException::exporterNotImplemented('html');
    }
}
