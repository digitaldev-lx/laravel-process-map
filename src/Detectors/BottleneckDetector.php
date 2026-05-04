<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Detectors;

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;

/**
 * Surfaces likely bottlenecks. Output is always hedged ("Potential…",
 * "May indicate…") because the analysis is heuristic.
 */
final class BottleneckDetector
{
    /**
     * @param  list<DiscoveredProcess>  $processes
     * @return list<DiscoveredProcess>
     */
    public function annotate(array $processes, ProcessMapResult $result): array
    {
        return array_map(
            fn (DiscoveredProcess $p): DiscoveredProcess => $this->annotateOne($p, $result),
            $processes,
        );
    }

    private function annotateOne(DiscoveredProcess $process, ProcessMapResult $result): DiscoveredProcess
    {
        $hints = [];

        // "Notifications may run synchronously" — only true when there is no
        // job dispatched AND no listener-with-ShouldQueue in the process.
        // Otherwise the notification is already pushed off the request thread.
        $notificationsCount = count($process->components['notifications'] ?? []);
        $jobsCount = count($process->components['jobs'] ?? []);
        $hasQueuedListener = $this->hasQueuedListener($process, $result);

        if ($notificationsCount > 0 && $jobsCount === 0 && ! $hasQueuedListener) {
            $hints[] = "Notifications in '{$process->name}' may run synchronously: no jobs dispatched and no queued listeners.";
        }

        if (count($process->components['events'] ?? []) === 0 && count($process->components['actions'] ?? []) > 1) {
            $hints[] = "Process '{$process->name}' has multiple actions but no events: consider firing one to decouple side effects.";
        }

        if (count($process->components['controllers'] ?? []) > 0) {
            foreach ($process->components['controllers'] as $controllerClass) {
                $hints = array_merge($hints, $this->controllerHints($controllerClass, $result));
            }
        }

        foreach ($process->components['jobs'] ?? [] as $jobClass) {
            $hints = array_merge($hints, $this->jobHints($jobClass, $result));
        }

        return new DiscoveredProcess(
            name: $process->name,
            entity: $process->entity,
            automationLevel: $process->automationLevel,
            components: $process->components,
            relations: $process->relations,
            potentialBottlenecks: array_values(array_unique($hints)),
            risks: $process->risks,
            recommendations: $process->recommendations,
        );
    }

    /**
     * @return list<string>
     */
    private function controllerHints(string $controller, ProcessMapResult $result): array
    {
        $class = $this->lookupClass($result, $controller, ScannerType::Controller);

        if ($class === null) {
            return [];
        }

        $hints = [];

        if (count($class->methods) > 8) {
            $hints[] = "Controller '{$class->shortName}' exposes ".count($class->methods).' public actions: consider splitting it.';
        }

        return $hints;
    }

    /**
     * @return list<string>
     */
    private function jobHints(string $job, ProcessMapResult $result): array
    {
        $class = $this->lookupClass($result, $job, ScannerType::Job);

        if ($class === null) {
            return [];
        }

        $hints = [];

        if (! ($class->metadata['should_queue'] ?? false)) {
            $hints[] = "Job '{$class->shortName}' does not implement ShouldQueue: it may run synchronously.";
        }

        if (($class->metadata['queue'] ?? null) === null && ($class->metadata['should_queue'] ?? false)) {
            $hints[] = "Job '{$class->shortName}' has no explicit queue: heavy work may end up on the default queue.";
        }

        return $hints;
    }

    private function hasQueuedListener(DiscoveredProcess $process, ProcessMapResult $result): bool
    {
        foreach ($process->components['listeners'] ?? [] as $listenerClass) {
            $class = $this->lookupClass($result, $listenerClass, ScannerType::Listener);

            if ($class !== null && ($class->metadata['should_queue'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    private function lookupClass(ProcessMapResult $result, string $fqcn, ScannerType $type): ?DiscoveredClass
    {
        foreach ($result->classes as $class) {
            if ($class->className === $fqcn && $class->type === $type) {
                return $class;
            }
        }

        return null;
    }
}
