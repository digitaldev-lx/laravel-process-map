<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Support;

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;

/**
 * Resolves a file path to its fully qualified class name by reading the
 * analysed application's `composer.json` PSR-4 autoload mapping.
 *
 * The resolver never autoloads or instantiates the target class — that is the
 * job of `AbstractAstScanner`. Here we only do path arithmetic.
 *
 * Supports modular layouts (DDD, nWidart/laravel-modules) because we read the
 * actual `composer.json` of the analysed app instead of hard-coding `app/`.
 */
final class ComposerAutoloadResolver
{
    /** @var array<string, list<string>>  namespace prefix → list of absolute directory paths */
    private array $psr4 = [];

    public function __construct(private readonly string $basePath)
    {
        $this->loadComposerFile();
    }

    /**
     * @return array<string, list<string>>
     */
    public function psr4Mappings(): array
    {
        return $this->psr4;
    }

    /**
     * Resolve a file path to its FQCN according to PSR-4. Returns null if the
     * file lives outside any registered autoload root.
     */
    public function resolveClassFromPath(string $absolutePath): ?string
    {
        $absolutePath = $this->normalisePath($absolutePath);

        foreach ($this->psr4 as $prefix => $dirs) {
            foreach ($dirs as $dir) {
                $dir = $this->normalisePath($dir);

                if ($dir === '' || ! str_starts_with($absolutePath, $dir.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $relative = substr($absolutePath, strlen($dir) + 1);
                $relative = preg_replace('/\.php$/', '', $relative);

                if ($relative === null || $relative === '') {
                    continue;
                }

                $fqcn = rtrim($prefix, '\\').'\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

                return ltrim($fqcn, '\\');
            }
        }

        return null;
    }

    private function loadComposerFile(): void
    {
        $composerPath = $this->basePath.DIRECTORY_SEPARATOR.'composer.json';

        if (! is_file($composerPath)) {
            throw ProcessMapException::missingComposerFile($composerPath);
        }

        $contents = file_get_contents($composerPath);

        if ($contents === false) {
            throw ProcessMapException::missingComposerFile($composerPath);
        }

        /** @var array{autoload?: array{psr-4?: array<string, string|list<string>>}, autoload-dev?: array{psr-4?: array<string, string|list<string>>}} $decoded */
        $decoded = json_decode($contents, true) ?: [];

        $this->mergeMappings($decoded['autoload']['psr-4'] ?? []);
        $this->mergeMappings($decoded['autoload-dev']['psr-4'] ?? []);
    }

    /**
     * @param  array<string, string|list<string>>  $mappings
     */
    private function mergeMappings(array $mappings): void
    {
        foreach ($mappings as $prefix => $value) {
            $dirs = is_array($value) ? $value : [$value];

            foreach ($dirs as $dir) {
                $absolute = $this->normalisePath(
                    rtrim($this->basePath, '/\\').DIRECTORY_SEPARATOR.trim($dir, '/\\')
                );

                $this->psr4[$prefix] ??= [];

                if (! in_array($absolute, $this->psr4[$prefix], true)) {
                    $this->psr4[$prefix][] = $absolute;
                }
            }
        }
    }

    private function normalisePath(string $path): string
    {
        $real = realpath($path);

        if ($real !== false) {
            return $real;
        }

        return rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}
