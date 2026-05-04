<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Data;

/**
 * Top-level data object produced by ApplicationScanner. Every exporter
 * receives an instance of this class and serialises it to its own format.
 *
 * The shape is schema-versioned via the `schemaVersion` property — bump it
 * any time the JSON output makes a breaking change so consumers can detect
 * format drift.
 */
final class ProcessMapResult
{
    public const string SCHEMA_VERSION = '0.1';

    /**
     * @param  array{name: string, environment: string, laravel_version: string, php_version: string}  $app
     * @param  list<DiscoveredClass>  $classes
     * @param  list<DiscoveredRoute>  $routes
     * @param  list<DiscoveredProcess>  $processes
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public readonly string $generatedAt,
        public readonly string $packageName,
        public readonly string $packageVersion,
        public readonly array $app,
        public readonly ProcessMapSummary $summary,
        public readonly array $classes = [],
        public readonly array $routes = [],
        public readonly array $processes = [],
        public readonly array $recommendations = [],
        public readonly string $schemaVersion = self::SCHEMA_VERSION,
    ) {}

    /**
     * Return a copy with the supplied processes replacing whatever is
     * currently set. Used by detectors that run after the initial scan.
     *
     * @param  list<DiscoveredProcess>  $processes
     * @param  list<string>  $recommendations
     */
    public function withProcesses(array $processes, array $recommendations = []): self
    {
        return new self(
            generatedAt: $this->generatedAt,
            packageName: $this->packageName,
            packageVersion: $this->packageVersion,
            app: $this->app,
            summary: new ProcessMapSummary(
                models: $this->summary->models,
                controllers: $this->summary->controllers,
                actions: $this->summary->actions,
                jobs: $this->summary->jobs,
                events: $this->summary->events,
                listeners: $this->summary->listeners,
                notifications: $this->summary->notifications,
                policies: $this->summary->policies,
                commands: $this->summary->commands,
                routes: $this->summary->routes,
                processes: count($processes),
            ),
            classes: $this->classes,
            routes: $this->routes,
            processes: $processes,
            recommendations: $recommendations !== [] ? $recommendations : $this->recommendations,
            schemaVersion: $this->schemaVersion,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'generated_at' => $this->generatedAt,
            'package' => $this->packageName,
            'version' => $this->packageVersion,
            'app' => $this->app,
            'summary' => $this->summary->toArray(),
            'classes' => array_map(static fn (DiscoveredClass $c): array => $c->toArray(), $this->classes),
            'routes' => array_map(static fn (DiscoveredRoute $r): array => $r->toArray(), $this->routes),
            'processes' => array_map(static fn (DiscoveredProcess $p): array => $p->toArray(), $this->processes),
            'recommendations' => $this->recommendations,
        ];
    }
}
