<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Data;

use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;

final class DiscoveredProcess
{
    /**
     * @param  array<string, list<string>>  $components  e.g. ['models' => ['App\\Models\\Lead'], 'actions' => [...]]
     * @param  list<DiscoveredRelation>  $relations
     * @param  list<string>  $potentialBottlenecks
     * @param  list<string>  $risks
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public readonly string $name,
        public readonly string $entity,
        public readonly AutomationLevel $automationLevel,
        public readonly array $components,
        public readonly array $relations = [],
        public readonly array $potentialBottlenecks = [],
        public readonly array $risks = [],
        public readonly array $recommendations = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'entity' => $this->entity,
            'automation_level' => $this->automationLevel->value,
            'components' => $this->components,
            'relations' => array_map(static fn (DiscoveredRelation $r): array => $r->toArray(), $this->relations),
            'potential_bottlenecks' => $this->potentialBottlenecks,
            'risks' => $this->risks,
            'recommendations' => $this->recommendations,
        ];
    }
}
