<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Get route map')]
#[Description('Returns the route table, optionally filtered by HTTP method, middleware, or process slug (matches via the controller class membership).')]
#[IsReadOnly]
final class GetRouteMapTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'process' => $schema->string()->description('Restrict to routes belonging to this process slug/name/entity.'),
            'method' => $schema->string()->description('HTTP method (GET, POST, ...).'),
            'middleware' => $schema->string()->description('Middleware substring (matches if any middleware contains this token).'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $routes = $this->repository->routes();
        $processSlug = $request->get('process');
        $method = $request->get('method');
        $middleware = $request->get('middleware');

        if (is_string($processSlug) && $processSlug !== '') {
            $process = $this->repository->findProcess($processSlug);

            if ($process === null) {
                $this->processNotFound($processSlug);
            }

            $controllers = (array) ($process['components']['controllers'] ?? []);

            $routes = array_values(array_filter(
                $routes,
                static fn (array $r): bool => in_array($r['controller_class'] ?? null, $controllers, true),
            ));
        }

        if (is_string($method) && $method !== '') {
            $upper = strtoupper($method);
            $routes = array_values(array_filter(
                $routes,
                static fn (array $r): bool => in_array($upper, array_map('strtoupper', (array) ($r['methods'] ?? [])), true),
            ));
        }

        if (is_string($middleware) && $middleware !== '') {
            $routes = array_values(array_filter(
                $routes,
                static function (array $r) use ($middleware): bool {
                    foreach ((array) ($r['middleware'] ?? []) as $entry) {
                        if (is_string($entry) && str_contains($entry, $middleware)) {
                            return true;
                        }
                    }

                    return false;
                },
            ));
        }

        $routes = array_slice($routes, 0, $this->guard->maxRoutesReturned());

        return ['routes' => $routes, 'count' => count($routes)];
    }
}
