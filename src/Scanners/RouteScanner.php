<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Scanners;

use Closure;
use DigitaldevLx\LaravelProcessMap\Contracts\Scanner;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredRoute;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

/**
 * Reads the application's already-registered routes from the framework's
 * router. The router is supplied via the container, so the scanner picks up
 * web, api and console routes alike (within the limits of how Laravel
 * exposes them — closure routes are kept but flagged).
 */
final class RouteScanner implements Scanner
{
    /**
     * @param  list<string>  $ignoredUriPrefixes  routes whose URI starts with any of these are skipped
     */
    public function __construct(
        private readonly Router $router,
        private readonly array $ignoredUriPrefixes = [],
    ) {}

    public function type(): ScannerType
    {
        return ScannerType::Route;
    }

    /**
     * @return list<DiscoveredRoute>
     */
    public function scan(): array
    {
        $discovered = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if ($this->shouldIgnore($route->uri())) {
                continue;
            }

            $discovered[] = $this->describe($route);
        }

        return $discovered;
    }

    private function shouldIgnore(string $uri): bool
    {
        $normalised = ltrim($uri, '/');

        foreach ($this->ignoredUriPrefixes as $prefix) {
            $needle = ltrim($prefix, '/');

            if ($needle !== '' && str_starts_with($normalised, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function describe(Route $route): DiscoveredRoute
    {
        $action = $route->getActionName();
        $uses = $route->getAction('uses');

        $controllerClass = null;
        $controllerMethod = null;

        if (is_string($uses) && str_contains($uses, '@')) {
            [$controllerClass, $controllerMethod] = explode('@', $uses, 2);
        } elseif (is_string($uses) && $uses !== '' && ! str_ends_with($uses, 'Closure')) {
            $controllerClass = $uses;
        } elseif ($uses instanceof Closure) {
            $action = 'Closure';
        }

        return new DiscoveredRoute(
            methods: array_values($route->methods()),
            uri: $route->uri(),
            name: $route->getName(),
            action: $action,
            controllerClass: $controllerClass,
            controllerMethod: $controllerMethod,
            middleware: array_values(array_unique(array_map('strval', $route->gatherMiddleware()))),
            domain: $route->getDomain(),
        );
    }
}
