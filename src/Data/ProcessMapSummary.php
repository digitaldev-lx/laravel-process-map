<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Data;

final class ProcessMapSummary
{
    public function __construct(
        public readonly int $models = 0,
        public readonly int $controllers = 0,
        public readonly int $actions = 0,
        public readonly int $jobs = 0,
        public readonly int $events = 0,
        public readonly int $listeners = 0,
        public readonly int $notifications = 0,
        public readonly int $policies = 0,
        public readonly int $commands = 0,
        public readonly int $routes = 0,
        public readonly int $processes = 0,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'models' => $this->models,
            'controllers' => $this->controllers,
            'actions' => $this->actions,
            'jobs' => $this->jobs,
            'events' => $this->events,
            'listeners' => $this->listeners,
            'notifications' => $this->notifications,
            'policies' => $this->policies,
            'commands' => $this->commands,
            'routes' => $this->routes,
            'processes' => $this->processes,
        ];
    }
}
