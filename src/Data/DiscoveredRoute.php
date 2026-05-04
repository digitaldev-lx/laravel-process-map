<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Data;

final class DiscoveredRoute
{
    /**
     * @param  list<string>  $methods  HTTP verbs
     * @param  list<string>  $middleware
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $uri,
        public readonly ?string $name,
        public readonly string $action,
        public readonly ?string $controllerClass,
        public readonly ?string $controllerMethod,
        public readonly array $middleware = [],
        public readonly ?string $domain = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'methods' => $this->methods,
            'uri' => $this->uri,
            'name' => $this->name,
            'action' => $this->action,
            'controller_class' => $this->controllerClass,
            'controller_method' => $this->controllerMethod,
            'middleware' => $this->middleware,
            'domain' => $this->domain,
        ];
    }
}
