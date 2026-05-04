<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Data;

final class DiscoveredRelation
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly string $type,
        public readonly ?string $description = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'type' => $this->type,
            'description' => $this->description,
        ];
    }
}
