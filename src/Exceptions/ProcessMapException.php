<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Exceptions;

use RuntimeException;

class ProcessMapException extends RuntimeException
{
    public static function missingComposerFile(string $path): self
    {
        return new self("composer.json not found at: {$path}");
    }

    public static function unwritableOutputPath(string $path): self
    {
        return new self("Output path is not writable: {$path}");
    }

    public static function exporterNotImplemented(string $name): self
    {
        return new self("Exporter '{$name}' is not yet implemented in this release.");
    }
}
