<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Exporters;

use DigitaldevLx\LaravelProcessMap\Contracts\Exporter;
use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;

abstract class AbstractExporter implements Exporter
{
    protected function persist(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw ProcessMapException::unwritableOutputPath($directory);
        }

        if (file_put_contents($path, $contents) === false) {
            throw ProcessMapException::unwritableOutputPath($path);
        }
    }
}
