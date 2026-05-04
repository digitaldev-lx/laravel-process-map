<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Support;

use Generator;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Thin wrapper over Symfony Finder. Centralises filtering rules so every
 * scanner sees the same view of the analysed application's filesystem
 * (only `.php` files, ignoring vendor/dotfiles by default).
 */
final class FileSystemScanner
{
    /**
     * Iterate over all PHP source files inside the given directories. Missing
     * directories are silently skipped — apps may have a subset of the
     * conventional `app/Actions`, `app/Jobs`, etc.
     *
     * @param  list<string>  $directories  absolute paths
     * @return Generator<int, SplFileInfo>
     */
    public function findPhpFiles(array $directories): Generator
    {
        $existing = array_values(array_filter(
            $directories,
            static fn (string $dir): bool => is_dir($dir),
        ));

        if ($existing === []) {
            return;
        }

        $finder = Finder::create()
            ->files()
            ->in($existing)
            ->name('*.php')
            ->notPath('vendor')
            ->ignoreUnreadableDirs()
            ->ignoreDotFiles(true);

        foreach ($finder as $file) {
            yield $file;
        }
    }
}
