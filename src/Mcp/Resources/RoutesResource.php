<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;

#[Uri('process-map://routes')]
#[Title('Routes Index')]
#[Description('All registered routes with method, URI, name, controller@method and middleware.')]
final class RoutesResource extends AbstractProcessMapResource
{
    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        $routes = array_slice($this->repository->routes(), 0, $this->guard->maxRoutesReturned());

        return ['routes' => $routes, 'count' => count($routes)];
    }
}
