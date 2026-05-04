<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Data;

use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;

final class DiscoveredAutomation
{
    /**
     * @param  list<string>  $signals  human-readable evidence used to compute the level
     */
    public function __construct(
        public readonly string $subject,
        public readonly AutomationLevel $level,
        public readonly array $signals = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'level' => $this->level->value,
            'signals' => $this->signals,
        ];
    }
}
