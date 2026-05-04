<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Contracts;

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;

interface ProcessDetector
{
    /**
     * Inspect an in-progress result (with classes/routes already populated)
     * and return the list of detected processes. Implementations should not
     * mutate the input.
     *
     * @return list<DiscoveredProcess>
     */
    public function detect(ProcessMapResult $partial): array;
}
