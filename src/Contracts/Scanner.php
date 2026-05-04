<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Contracts;

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;

/**
 * Marker interface implemented by every scanner. The concrete return type of
 * `scan()` varies by scanner (list of DiscoveredClass for class scanners,
 * list of DiscoveredRoute for the route scanner), so the contract intentionally
 * does not constrain it beyond `array`.
 */
interface Scanner
{
    public function type(): ScannerType;

    /**
     * Run the scanner.
     *
     * @return array<int, mixed>
     */
    public function scan(): array;
}
