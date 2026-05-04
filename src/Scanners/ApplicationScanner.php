<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Scanners;

use Composer\InstalledVersions;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredRoute;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapSummary;
use DigitaldevLx\LaravelProcessMap\Detectors\AutomationDetector;
use DigitaldevLx\LaravelProcessMap\Detectors\BottleneckDetector;
use DigitaldevLx\LaravelProcessMap\Detectors\NamingConventionProcessDetector;
use DigitaldevLx\LaravelProcessMap\Detectors\RiskDetector;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Throwable;

/**
 * Top-level orchestrator. Reads the package config to know which scanners
 * are enabled, instantiates each one with its dependencies, runs them, and
 * folds the results into a single {@see ProcessMapResult}.
 *
 * Process detection and recommendations are intentionally deferred to the
 * detectors (Phase 9) — this class only produces the raw, factual map.
 */
final class ApplicationScanner
{
    public const string PACKAGE_NAME = 'digitaldev-lx/laravel-process-map';

    /**
     * Resolve the installed package version at runtime so it stays in sync
     * with the Composer tag. Falls back to a sentinel string when the package
     * is being developed in-place (Composer metadata not yet generated).
     */
    public static function packageVersion(): string
    {
        try {
            $version = InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);

            if (is_string($version) && $version !== '') {
                return $version;
            }
        } catch (Throwable) {
            // Composer\InstalledVersions throws OutOfBoundsException when the
            // package is not yet installed (e.g. while running its own tests).
        }

        return '1.0.0';
    }

    public function __construct(
        private readonly Application $app,
        private readonly Repository $config,
        private readonly NamespaceResolver $namespaceResolver = new NamespaceResolver,
        private readonly ClassNameResolver $classNameResolver = new ClassNameResolver,
        private readonly FileSystemScanner $fileSystemScanner = new FileSystemScanner,
    ) {}

    public function scan(): ProcessMapResult
    {
        $base = $this->app->basePath();

        $classes = array_merge(
            $this->scanClasses('models', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new ModelScanner($n, $c, $f, $dirs, $base)),
            $this->scanClasses('controllers', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new ControllerScanner($n, $c, $f, $dirs, $base)),
            $this->scanClasses('actions', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new ActionScanner($n, $c, $f, $dirs, $base)),
            $this->scanClasses('jobs', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new JobScanner($n, $c, $f, $dirs, $base)),
            $this->scanClasses('events', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new EventScanner($n, $c, $f, $dirs, $base)),
            $this->scanClasses('listeners', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new ListenerScanner($n, $c, $f, $dirs, $base)),
            $this->scanClasses('notifications', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new NotificationScanner($n, $c, $f, $dirs, $base)),
            $this->scanClasses('policies', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new PolicyScanner($n, $c, $f, $dirs, $base)),
            $this->scanClasses('commands', static fn (NamespaceResolver $n, ClassNameResolver $c, FileSystemScanner $f, array $dirs) => new CommandScanner($n, $c, $f, $dirs, $base)),
        );

        $routes = $this->scanRoutes();
        $schedule = $this->scanSchedule();
        $broadcasts = $this->scanBroadcasts();

        $allClasses = array_merge(
            $classes,
            $this->describeBroadcastsAsClasses($broadcasts),
            $this->describeScheduleAsClasses($schedule),
        );

        $partial = new ProcessMapResult(
            generatedAt: gmdate('c'),
            packageName: self::PACKAGE_NAME,
            packageVersion: self::packageVersion(),
            app: $this->describeApp(),
            summary: $this->buildSummary($classes, $routes),
            classes: $allClasses,
            routes: $routes,
        );

        return $this->detectProcesses($partial);
    }

    private function detectProcesses(ProcessMapResult $partial): ProcessMapResult
    {
        if (! $this->config->get('process-map.process_detection.enabled', true)) {
            return $partial;
        }

        $verbs = (array) $this->config->get('process-map.process_detection.keywords', []);
        $suffixes = (array) $this->config->get('process-map.process_detection.business_suffixes', []);

        /** @var list<string> $verbList */
        $verbList = array_values(array_filter($verbs, 'is_string'));
        /** @var list<string> $suffixList */
        $suffixList = array_values(array_filter($suffixes, 'is_string'));

        $detector = $verbList !== [] || $suffixList !== []
            ? new NamingConventionProcessDetector($verbList, $suffixList)
            : new NamingConventionProcessDetector;

        $processes = $detector->detect($partial);
        $processes = (new AutomationDetector)->classify($processes);
        $processes = (new BottleneckDetector)->annotate($processes, $partial);
        $processes = (new RiskDetector)->annotate($processes, $partial);

        return $partial->withProcesses($processes);
    }

    /**
     * @param  callable(NamespaceResolver, ClassNameResolver, FileSystemScanner, list<string>): AbstractAstScanner  $factory
     * @return list<DiscoveredClass>
     */
    private function scanClasses(string $configKey, callable $factory): array
    {
        if (! $this->config->get('process-map.scan.'.$configKey, true)) {
            return [];
        }

        $directories = $this->resolveDirectories($configKey);

        if ($directories === []) {
            return [];
        }

        $scanner = $factory($this->namespaceResolver, $this->classNameResolver, $this->fileSystemScanner, $directories);

        return $scanner->scan();
    }

    /**
     * @return list<string>
     */
    private function resolveDirectories(string $key): array
    {
        /** @var list<string> $relative */
        $relative = (array) $this->config->get('process-map.directories.'.$key, []);

        $base = $this->app->basePath();

        return array_values(array_map(
            static fn (string $dir): string => rtrim($base, '/').'/'.ltrim($dir, '/'),
            $relative,
        ));
    }

    /**
     * @return list<DiscoveredRoute>
     */
    private function scanRoutes(): array
    {
        if (! $this->config->get('process-map.scan.routes', true)) {
            return [];
        }

        if (! $this->app->bound(Router::class) && ! $this->app->bound('router')) {
            return [];
        }

        /** @var Router $router */
        $router = $this->app->make('router');

        $ignored = (array) $this->config->get('process-map.routes.ignore_uri_prefixes', []);
        /** @var list<string> $ignoredList */
        $ignoredList = array_values(array_filter($ignored, 'is_string'));

        return (new RouteScanner($router, $ignoredList))->scan();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scanSchedule(): array
    {
        if (! $this->config->get('process-map.scan.schedule', true)) {
            return [];
        }

        if (! $this->app->bound(Schedule::class)) {
            return [];
        }

        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        return (new ScheduleScanner($schedule))->scan();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scanBroadcasts(): array
    {
        if (! $this->config->get('process-map.scan.broadcasting', true)) {
            return [];
        }

        $relative = (array) $this->config->get('process-map.broadcasts.files', ['routes/channels.php']);
        /** @var list<string> $relativeList */
        $relativeList = array_values(array_filter($relative, 'is_string'));

        $files = array_map(
            fn (string $file): string => $this->app->basePath(ltrim($file, '/')),
            $relativeList,
        );

        return (new BroadcastScanner($this->namespaceResolver, $files))->scan();
    }

    /**
     * @return array{name: string, environment: string, laravel_version: string, php_version: string}
     */
    private function describeApp(): array
    {
        $name = $this->config->get('app.name');
        $environment = $this->app->environment();

        return [
            'name' => is_string($name) ? $name : 'Laravel',
            'environment' => is_string($environment) ? $environment : 'production',
            'laravel_version' => $this->app->version(),
            'php_version' => PHP_VERSION,
        ];
    }

    /**
     * @param  list<DiscoveredClass>  $classes
     * @param  list<DiscoveredRoute>  $routes
     */
    private function buildSummary(array $classes, array $routes): ProcessMapSummary
    {
        $counts = [
            ScannerType::Model->value => 0,
            ScannerType::Controller->value => 0,
            ScannerType::Action->value => 0,
            ScannerType::Job->value => 0,
            ScannerType::Event->value => 0,
            ScannerType::Listener->value => 0,
            ScannerType::Notification->value => 0,
            ScannerType::Policy->value => 0,
            ScannerType::Command->value => 0,
        ];

        foreach ($classes as $class) {
            $counts[$class->type->value] = ($counts[$class->type->value] ?? 0) + 1;
        }

        return new ProcessMapSummary(
            models: $counts[ScannerType::Model->value],
            controllers: $counts[ScannerType::Controller->value],
            actions: $counts[ScannerType::Action->value],
            jobs: $counts[ScannerType::Job->value],
            events: $counts[ScannerType::Event->value],
            listeners: $counts[ScannerType::Listener->value],
            notifications: $counts[ScannerType::Notification->value],
            policies: $counts[ScannerType::Policy->value],
            commands: $counts[ScannerType::Command->value],
            routes: count($routes),
            processes: 0,
        );
    }

    /**
     * Schedule entries do not map to a class, but it is convenient to expose
     * them as `DiscoveredClass` instances of type `Schedule` so detectors and
     * exporters can iterate uniformly. The class name slot holds the command
     * string, methods stays empty, metadata carries the rest.
     *
     * @param  list<array<string, mixed>>  $schedule
     * @return list<DiscoveredClass>
     */
    private function describeScheduleAsClasses(array $schedule): array
    {
        $classes = [];

        foreach ($schedule as $entry) {
            $command = is_string($entry['command'] ?? null) ? $entry['command'] : 'closure';

            $classes[] = new DiscoveredClass(
                type: ScannerType::Schedule,
                className: $command,
                shortName: $command,
                namespace: '',
                filePath: 'routes/console.php',
                methods: [],
                traits: [],
                interfaces: [],
                references: [],
                metadata: $entry,
            );
        }

        return $classes;
    }

    /**
     * @param  list<array<string, mixed>>  $broadcasts
     * @return list<DiscoveredClass>
     */
    private function describeBroadcastsAsClasses(array $broadcasts): array
    {
        $classes = [];

        foreach ($broadcasts as $entry) {
            $name = is_string($entry['name'] ?? null) ? $entry['name'] : 'unnamed';

            $classes[] = new DiscoveredClass(
                type: ScannerType::Broadcast,
                className: $name,
                shortName: $name,
                namespace: '',
                filePath: is_string($entry['file'] ?? null) ? $entry['file'] : 'routes/channels.php',
                methods: [],
                traits: [],
                interfaces: [],
                references: [],
                metadata: $entry,
            );
        }

        return $classes;
    }
}
