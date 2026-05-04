<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Providers;

use DigitaldevLx\LaravelProcessMap\Commands\InstallCommand;
use DigitaldevLx\LaravelProcessMap\Commands\JsonCommand;
use DigitaldevLx\LaravelProcessMap\Commands\MermaidCommand;
use DigitaldevLx\LaravelProcessMap\Commands\ReportCommand;
use DigitaldevLx\LaravelProcessMap\Commands\ScanCommand;
use DigitaldevLx\LaravelProcessMap\Exporters\JsonExporter;
use DigitaldevLx\LaravelProcessMap\Exporters\MarkdownExporter;
use DigitaldevLx\LaravelProcessMap\Exporters\MermaidExporter;
use DigitaldevLx\LaravelProcessMap\ProcessMap;
use DigitaldevLx\LaravelProcessMap\Scanners\ApplicationScanner;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

class ProcessMapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/process-map.php',
            'process-map'
        );

        $this->app->singleton(ApplicationScanner::class);

        $this->app->singleton(JsonExporter::class, function ($app): JsonExporter {
            /** @var Repository $config */
            $config = $app->make(Repository::class);

            return new JsonExporter([
                'include_method_names' => (bool) $config->get('process-map.privacy.include_method_names', true),
                'include_file_paths' => (bool) $config->get('process-map.privacy.include_file_paths', true),
            ]);
        });

        $this->app->singleton(MarkdownExporter::class);

        $this->app->singleton(MermaidExporter::class, function ($app): MermaidExporter {
            /** @var Repository $config */
            $config = $app->make(Repository::class);
            $max = (int) $config->get('process-map.mermaid.max_nodes', 200);

            return new MermaidExporter($max);
        });

        $this->app->singleton(ProcessMap::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/process-map.php' => config_path('process-map.php'),
            ], 'process-map-config');

            $this->commands([
                InstallCommand::class,
                ScanCommand::class,
                ReportCommand::class,
                JsonCommand::class,
                MermaidCommand::class,
            ]);
        }
    }
}
