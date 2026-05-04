<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Contracts;

use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;

interface Exporter
{
    /**
     * Render the result. When `$path` is provided, the rendered string is
     * also written to that path. Returns the rendered content either way so
     * callers can stream it without re-reading the file.
     */
    public function export(ProcessMapResult $result, ?string $path = null): string;
}
