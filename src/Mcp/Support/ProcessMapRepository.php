<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Support;

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Loads and queries the canonical `process-map.json` artefact produced
 * by `php artisan process-map:scan`. The MCP layer talks to this class
 * and never touches the JSON file directly.
 *
 * Read-only by construction: every accessor returns arrays cloned from
 * the parsed JSON. The repository never writes anywhere.
 */
final class ProcessMapRepository
{
    private const string CACHE_KEY = 'process-map.mcp.snapshot';

    public function __construct(
        private readonly Config $config,
        private readonly Cache $cache,
        private readonly ProcessMapSanitizer $sanitizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $cacheEnabled = (bool) $this->config->get('process-map.mcp.cache.enabled', true);

        if (! $cacheEnabled) {
            return $this->loadAndSanitise();
        }

        $ttl = (int) $this->config->get('process-map.mcp.cache.ttl_seconds', 300);

        /** @var array<string, mixed> $snapshot */
        $snapshot = $this->cache->remember(self::CACHE_KEY, $ttl, fn (): array => $this->loadAndSanitise());

        return $snapshot;
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    public function path(): string
    {
        $output = (string) $this->config->get('process-map.output_path', storage_path('app/process-map'));

        return rtrim($output, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'process-map.json';
    }

    public function markdownPath(): string
    {
        $output = (string) $this->config->get('process-map.output_path', storage_path('app/process-map'));

        return rtrim($output, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'process-map.md';
    }

    public function mermaidPath(): string
    {
        $output = (string) $this->config->get('process-map.output_path', storage_path('app/process-map'));

        return rtrim($output, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'process-map.mmd';
    }

    public function exists(): bool
    {
        return is_file($this->path());
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $snapshot = $this->snapshot();

        $summary = is_array($snapshot['summary'] ?? null) ? $snapshot['summary'] : [];
        $app = is_array($snapshot['app'] ?? null) ? $snapshot['app'] : [];

        return [
            'app' => $app,
            'generated_at' => $snapshot['generated_at'] ?? null,
            'schema_version' => $snapshot['schema_version'] ?? null,
            'package_version' => $snapshot['version'] ?? null,
            'totals' => $summary,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function processes(): array
    {
        $snapshot = $this->snapshot();
        $processes = is_array($snapshot['processes'] ?? null) ? $snapshot['processes'] : [];

        return array_values(array_filter($processes, 'is_array'));
    }

    /**
     * Find a process by slug, exact name (case-insensitive) or short name.
     *
     * @return array<string, mixed>|null
     */
    public function findProcess(string $needle): ?array
    {
        $needle = strtolower(trim($needle));

        if ($needle === '') {
            return null;
        }

        foreach ($this->processes() as $process) {
            $slug = strtolower((string) ($process['slug'] ?? ''));
            $name = strtolower((string) ($process['name'] ?? ''));
            $entity = strtolower((string) ($process['entity'] ?? ''));

            if ($slug === $needle || $name === $needle || $entity === $needle) {
                return $process;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function classes(): array
    {
        $snapshot = $this->snapshot();
        $classes = is_array($snapshot['classes'] ?? null) ? $snapshot['classes'] : [];

        return array_values(array_filter($classes, 'is_array'));
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function classesByType(): array
    {
        $grouped = [];

        foreach ($this->classes() as $class) {
            $type = is_string($class['type'] ?? null) ? $class['type'] : 'unknown';
            $grouped[$type][] = $class;
        }

        return $grouped;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function routes(): array
    {
        $snapshot = $this->snapshot();
        $routes = is_array($snapshot['routes'] ?? null) ? $snapshot['routes'] : [];

        return array_values(array_filter($routes, 'is_array'));
    }

    /**
     * @return list<array{process: string, slug: string, message: string}>
     */
    public function risks(): array
    {
        $risks = [];

        foreach ($this->processes() as $process) {
            $name = (string) ($process['name'] ?? 'unnamed');
            $slug = (string) ($process['slug'] ?? '');

            foreach ((array) ($process['risks'] ?? []) as $risk) {
                if (! is_string($risk) || $risk === '') {
                    continue;
                }
                $risks[] = ['process' => $name, 'slug' => $slug, 'message' => $risk];
            }
        }

        return $risks;
    }

    /**
     * @return list<array{process: string, slug: string, message: string}>
     */
    public function recommendations(): array
    {
        $recs = [];

        foreach ($this->processes() as $process) {
            $name = (string) ($process['name'] ?? 'unnamed');
            $slug = (string) ($process['slug'] ?? '');

            foreach ((array) ($process['recommendations'] ?? []) as $rec) {
                if (! is_string($rec) || $rec === '') {
                    continue;
                }
                $recs[] = ['process' => $name, 'slug' => $slug, 'message' => $rec];
            }

            foreach ((array) ($process['potential_bottlenecks'] ?? []) as $hint) {
                if (! is_string($hint) || $hint === '') {
                    continue;
                }
                $recs[] = ['process' => $name, 'slug' => $slug, 'message' => '[bottleneck] '.$hint];
            }
        }

        return $recs;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAndSanitise(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            throw new ProcessMapException(
                "No process map found at {$path}. Run `php artisan process-map:scan --all` first, ".
                'or call the `refresh_process_map` MCP tool if it is enabled.'
            );
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new ProcessMapException("Unable to read process map at {$path}.");
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new ProcessMapException("process-map.json at {$path} is not valid JSON: ".$e->getMessage());
        }

        return $this->sanitizer->sanitise($decoded);
    }
}
