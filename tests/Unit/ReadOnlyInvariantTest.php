<?php

declare(strict_types=1);

/**
 * Guards the invariant that the package never executes business code in the
 * analysed application. Catches accidental usage of helpers like dispatch(),
 * event(), Notification::send, DB::, etc. inside src/.
 */
it('does not call any side-effect-producing helper inside src/', function (): void {
    $forbiddenPatterns = [
        '/\bDB::/',
        '/\bdispatch\(/',
        '/\bdispatch_sync\(/',
        '/\bdispatch_now\(/',
        '/\bevent\(/',
        '/\bbroadcast\(/',
        '/Notification::send\(/',
        '/Mail::send\(/',
        '/Http::get\(/',
        '/Http::post\(/',
    ];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__.'/../../src', RecursiveDirectoryIterator::SKIP_DOTS),
    );

    $offenders = [];

    foreach ($iterator as $file) {
        if (! ($file instanceof SplFileInfo) || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if ($contents === false) {
            continue;
        }

        foreach (explode("\n", $contents) as $line => $text) {
            $trim = ltrim($text);

            if (str_starts_with($trim, '//') || str_starts_with($trim, '*') || str_starts_with($trim, '/*')) {
                continue;
            }

            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match($pattern, $text) === 1) {
                    $offenders[] = $file->getFilename().':'.($line + 1).' → '.trim($text);
                }
            }
        }
    }

    expect($offenders)->toBe([], 'src/ must remain read-only. Offenders: '.implode('; ', $offenders));
});
