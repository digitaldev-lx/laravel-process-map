<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Exporters;

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Support\StrHelpers;

final class MermaidExporter extends AbstractExporter
{
    public function __construct(private readonly int $maxNodes = 200) {}

    public function export(ProcessMapResult $result, ?string $path = null): string
    {
        $diagrams = [];

        $diagrams[] = $this->overviewDiagram($result);

        foreach ($result->processes as $process) {
            $diagrams[] = $this->processDiagram($result, $process);
        }

        $output = implode("\n\n", $diagrams)."\n";

        if ($path !== null) {
            $this->persist($path, $output);
        }

        return $output;
    }

    private function overviewDiagram(ProcessMapResult $result): string
    {
        $lines = ['flowchart TD'];
        $nodeCount = 0;
        $truncated = false;

        foreach ($result->routes as $i => $route) {
            if ($nodeCount >= $this->maxNodes) {
                $truncated = true;
                break;
            }

            $id = 'route_'.$i;
            $label = implode(',', $route->methods).' '.$route->uri;
            $lines[] = "    {$id}[\"{$label}\"]";
            $nodeCount++;

            if ($route->controllerClass !== null) {
                $controllerId = StrHelpers::safeMermaidId($route->controllerClass);
                $lines[] = "    {$controllerId}[\"".$this->shortLabel($route->controllerClass).'"]';
                $method = $route->controllerMethod !== null ? "@{$route->controllerMethod}" : '';
                $lines[] = "    {$id} --> |HTTP{$method}| {$controllerId}";
                $nodeCount++;
            }
        }

        foreach ($result->classes as $class) {
            if ($nodeCount >= $this->maxNodes) {
                $truncated = true;
                break;
            }

            if ($class->references === []) {
                continue;
            }

            $fromId = StrHelpers::safeMermaidId($class->className);

            foreach ($class->references as $reference) {
                if (! $this->relevantReference($result, $reference)) {
                    continue;
                }

                $toId = StrHelpers::safeMermaidId($reference);
                $lines[] = "    {$fromId} --> {$toId}";
                $nodeCount++;
            }
        }

        if ($truncated) {
            array_unshift(
                $lines,
                "%% Diagram truncated at {$this->maxNodes} nodes — see the per-process diagrams below for the full picture.",
            );
        }

        return implode("\n", $lines);
    }

    private function processDiagram(ProcessMapResult $result, DiscoveredProcess $process): string
    {
        $title = '%% Process: '.$process->name;
        $lines = [$title, 'flowchart TD'];

        foreach ($process->components as $bucket => $classes) {
            foreach ($classes as $class) {
                $id = StrHelpers::safeMermaidId($class);
                $label = $this->shortLabel($class).'\\n('.rtrim((string) $bucket, 's').')';
                $lines[] = "    {$id}[\"{$label}\"]";
            }
        }

        $components = $process->components;

        $lines = array_merge(
            $lines,
            $this->edges($components['controllers'] ?? [], $components['actions'] ?? [], 'invokes'),
            $this->edges($components['actions'] ?? [], $components['jobs'] ?? [], 'dispatches'),
            $this->edges($components['actions'] ?? [], $components['events'] ?? [], 'fires'),
            $this->edges($components['events'] ?? [], $components['listeners'] ?? [], 'handled by'),
            $this->edges($components['listeners'] ?? [], $components['notifications'] ?? [], 'sends'),
        );

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $sources
     * @param  list<string>  $targets
     * @return list<string>
     */
    private function edges(array $sources, array $targets, string $label): array
    {
        if ($sources === [] || $targets === []) {
            return [];
        }

        $edges = [];

        foreach ($sources as $from) {
            foreach ($targets as $to) {
                $edges[] = '    '.StrHelpers::safeMermaidId($from).' --> |'.$label.'| '.StrHelpers::safeMermaidId($to);
            }
        }

        return $edges;
    }

    private function relevantReference(ProcessMapResult $result, string $reference): bool
    {
        $types = [
            ScannerType::Action,
            ScannerType::Job,
            ScannerType::Event,
            ScannerType::Listener,
            ScannerType::Notification,
            ScannerType::Model,
        ];

        foreach ($result->classes as $class) {
            if ($class->className === $reference && in_array($class->type, $types, true)) {
                return true;
            }
        }

        return false;
    }

    private function shortLabel(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }
}
