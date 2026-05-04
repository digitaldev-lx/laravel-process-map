<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Detectors;

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;

final class RiskDetector
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
        $risks = [];
        $recommendations = [];

        if (count($process->components['policies'] ?? []) === 0) {
            $risks[] = "Process '{$process->name}' has no associated policy: authorisation may be missing.";
            $recommendations[] = "Add a policy for the {$process->entity} model.";
        }

        foreach ($process->components['jobs'] ?? [] as $jobClass) {
            $class = $this->lookup($result, $jobClass, ScannerType::Job);

            if ($class === null) {
                continue;
            }

            if (($class->metadata['tries'] ?? null) === null && ($class->metadata['should_queue'] ?? false)) {
                $risks[] = "Job '{$class->shortName}' has no \$tries set: a transient error will retry indefinitely or not at all depending on driver defaults.";
            }

            if (($class->metadata['timeout'] ?? null) === null && ($class->metadata['should_queue'] ?? false)) {
                $risks[] = "Job '{$class->shortName}' has no \$timeout: long-running work may stall workers.";
            }
        }

        foreach ($process->components['commands'] ?? [] as $commandClass) {
            $class = $this->lookup($result, $commandClass, ScannerType::Command);

            if ($class === null) {
                continue;
            }

            $signature = strtolower((string) ($class->metadata['signature'] ?? ''));

            if (str_contains($signature, 'force') || str_contains($signature, 'destroy')) {
                $risks[] = "Command '{$class->shortName}' looks destructive (signature: '{$class->metadata['signature']}').";
            }
        }

        return new DiscoveredProcess(
            name: $process->name,
            entity: $process->entity,
            automationLevel: $process->automationLevel,
            components: $process->components,
            relations: $process->relations,
            potentialBottlenecks: $process->potentialBottlenecks,
            risks: array_values(array_unique($risks)),
            recommendations: array_values(array_unique(array_merge($process->recommendations, $recommendations))),
        );
    }

    private function lookup(ProcessMapResult $result, string $fqcn, ScannerType $type): ?DiscoveredClass
    {
        foreach ($result->classes as $class) {
            if ($class->className === $fqcn && $class->type === $type) {
                return $class;
            }
        }

        return null;
    }
}
