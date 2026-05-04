<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Detectors;

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;

/**
 * Assigns an {@see AutomationLevel} to each process based on the kinds of
 * components it ships. The scoring is intentionally simple and transparent
 * so consultants can explain the result.
 *
 *  - actions       +1 (the work has at least been extracted)
 *  - jobs          +2 (the work is queueable)
 *  - events        +1
 *  - listeners     +1
 *  - notifications +1
 *  - schedule      +2 (recurring without human input)
 *  - broadcasts    +1 (real-time delivery)
 */
final class AutomationDetector
{
    private const array WEIGHTS = [
        'actions' => 1,
        'events' => 1,
        'listeners' => 1,
        'notifications' => 1,
        'jobs' => 2,
        'schedule' => 2,
        'broadcasts' => 1,
    ];

    /**
     * @param  list<DiscoveredProcess>  $processes
     * @return list<DiscoveredProcess>
     */
    public function classify(array $processes): array
    {
        return array_map(fn (DiscoveredProcess $p): DiscoveredProcess => $this->classifyOne($p), $processes);
    }

    private function classifyOne(DiscoveredProcess $process): DiscoveredProcess
    {
        $score = 0;

        foreach (self::WEIGHTS as $bucket => $weight) {
            if (count($process->components[$bucket] ?? []) > 0) {
                $score += $weight;
            }
        }

        return new DiscoveredProcess(
            name: $process->name,
            entity: $process->entity,
            automationLevel: AutomationLevel::fromScore($score),
            components: $process->components,
            relations: $process->relations,
            potentialBottlenecks: $process->potentialBottlenecks,
            risks: $process->risks,
            recommendations: $process->recommendations,
            slug: $process->slug,
        );
    }
}
