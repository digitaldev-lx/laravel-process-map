<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Exporters;

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredRoute;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;

/**
 * Markdown exporter optimised for being fed back to an LLM (e.g. Claude Code)
 * as compact context. The format favours:
 *
 *  - Dense tables that surface FQCN + file path + role + relevant flags in
 *    one line per class.
 *  - Explicit "flow" lines that trace request → action → side effects so the
 *    reader does not have to chain references mentally.
 *  - "Unattached components" rather than full duplicate listings, to avoid
 *    repeating information already inside a process block.
 */
final class MarkdownExporter extends AbstractExporter
{
    public function export(ProcessMapResult $result, ?string $path = null): string
    {
        $clusteredClassNames = $this->clusteredClassNames($result);

        $sections = array_filter([
            $this->header($result),
            $this->summary($result),
            $this->processes($result),
            $this->unattached($result, $clusteredClassNames),
            $this->routesIndex($result),
            $this->scheduleIndex($result),
            $this->broadcastIndex($result),
            $this->footer(),
        ], static fn (string $s): bool => $s !== '');

        $markdown = implode("\n\n", $sections)."\n";

        if ($path !== null) {
            $this->persist($path, $markdown);
        }

        return $markdown;
    }

    private function header(ProcessMapResult $result): string
    {
        return <<<MD
        # Process Map: {$result->app['name']}

        > Static analysis snapshot — read-only.
        > Generated: `{$result->generatedAt}` · PHP `{$result->app['php_version']}` · Laravel `{$result->app['laravel_version']}` · Schema `{$result->schemaVersion}` · Package `{$result->packageName}@{$result->packageVersion}`
        MD;
    }

    private function summary(ProcessMapResult $result): string
    {
        $bottlenecks = 0;
        $risks = 0;

        foreach ($result->processes as $p) {
            $bottlenecks += count($p->potentialBottlenecks);
            $risks += count($p->risks);
        }

        $rows = [
            '| Metric | Count |',
            '| --- | ---: |',
            "| Models | {$result->summary->models} |",
            "| Controllers | {$result->summary->controllers} |",
            "| Actions | {$result->summary->actions} |",
            "| Jobs | {$result->summary->jobs} |",
            "| Events | {$result->summary->events} |",
            "| Listeners | {$result->summary->listeners} |",
            "| Notifications | {$result->summary->notifications} |",
            "| Policies | {$result->summary->policies} |",
            "| Commands | {$result->summary->commands} |",
            "| Routes | {$result->summary->routes} |",
            "| Detected processes | {$result->summary->processes} |",
            "| Potential bottlenecks | {$bottlenecks} |",
            "| Risks flagged | {$risks} |",
        ];

        return "## Summary\n\n".implode("\n", $rows);
    }

    private function processes(ProcessMapResult $result): string
    {
        if ($result->processes === []) {
            return "## Processes\n\n_No processes detected by the naming-convention heuristic._";
        }

        $blocks = ['## Processes'];

        foreach ($result->processes as $i => $process) {
            $blocks[] = $this->processBlock($i + 1, $process, $result);
        }

        return implode("\n\n", $blocks);
    }

    private function processBlock(int $index, DiscoveredProcess $process, ProcessMapResult $result): string
    {
        $lines = [];
        $lines[] = "### {$index}. {$process->name}";
        $lines[] = '';
        $lines[] = "- **Entity:** `{$process->entity}`";
        $lines[] = "- **Automation:** {$process->automationLevel->value}";

        $members = $this->processMembersTable($process, $result);

        if ($members !== '') {
            $lines[] = '';
            $lines[] = '#### Members';
            $lines[] = '';
            $lines[] = $members;
        }

        $flow = $this->processFlow($process, $result);

        if ($flow !== '') {
            $lines[] = '';
            $lines[] = '#### Flow';
            $lines[] = '';
            $lines[] = $flow;
        }

        if ($process->potentialBottlenecks !== [] || $process->risks !== [] || $process->recommendations !== []) {
            $lines[] = '';
            $lines[] = '#### Findings';

            foreach ($process->potentialBottlenecks as $hint) {
                $lines[] = "- ⚠ Bottleneck: {$hint}";
            }

            foreach ($process->risks as $risk) {
                $lines[] = "- 🛡 Risk: {$risk}";
            }

            foreach ($process->recommendations as $rec) {
                $lines[] = "- 💡 Recommendation: {$rec}";
            }
        }

        return implode("\n", $lines);
    }

    private function processMembersTable(DiscoveredProcess $process, ProcessMapResult $result): string
    {
        $rows = [
            '| Role | Class | File | Notes |',
            '| --- | --- | --- | --- |',
        ];

        $any = false;

        foreach ($process->components as $bucket => $classNames) {
            if ($classNames === []) {
                continue;
            }

            foreach ($classNames as $fqcn) {
                $class = $this->findClass($result, $fqcn);
                $role = $this->roleLabel((string) $bucket);
                $file = $class !== null && $class->filePath !== '' ? '`'.$class->filePath.'`' : '—';
                $notes = $class !== null ? $this->notesFor($class) : '';

                $rows[] = "| {$role} | `{$fqcn}` | {$file} | {$notes} |";
                $any = true;
            }
        }

        return $any ? implode("\n", $rows) : '';
    }

    private function processFlow(DiscoveredProcess $process, ProcessMapResult $result): string
    {
        $lines = [];

        // Routes → controllers
        foreach ($result->routes as $route) {
            if ($route->controllerClass === null) {
                continue;
            }

            if (! in_array($route->controllerClass, $process->components['controllers'] ?? [], true)) {
                continue;
            }

            $methods = implode(',', $route->methods);
            $lines[] = "- `{$methods} /{$route->uri}` → `{$this->shortName($route->controllerClass)}@{$route->controllerMethod}`";
        }

        // Controllers → actions
        foreach ($process->components['controllers'] ?? [] as $controllerFqcn) {
            $controller = $this->findClass($result, $controllerFqcn);

            if ($controller === null) {
                continue;
            }

            foreach ($controller->references as $ref) {
                if (in_array($ref, $process->components['actions'] ?? [], true)) {
                    $lines[] = "- `{$controller->shortName}` → invokes `{$this->shortName($ref)}`";
                }
            }
        }

        // Actions → jobs / events
        foreach ($process->components['actions'] ?? [] as $actionFqcn) {
            $action = $this->findClass($result, $actionFqcn);

            if ($action === null) {
                continue;
            }

            foreach ((array) ($action->metadata['dispatches_jobs'] ?? []) as $job) {
                if (! is_string($job)) {
                    continue;
                }
                $lines[] = "- `{$action->shortName}` → dispatches `{$this->shortName($job)}`";
            }

            foreach ((array) ($action->metadata['fires_events'] ?? []) as $event) {
                if (! is_string($event)) {
                    continue;
                }
                $lines[] = "- `{$action->shortName}` → fires `{$this->shortName($event)}`";
            }
        }

        // Events → listeners
        foreach ($process->components['listeners'] ?? [] as $listenerFqcn) {
            $listener = $this->findClass($result, $listenerFqcn);

            if ($listener === null) {
                continue;
            }

            $event = is_string($listener->metadata['listens_to'] ?? null) ? $listener->metadata['listens_to'] : null;

            if ($event !== null) {
                $queueTag = ($listener->metadata['should_queue'] ?? false) ? ' [queued]' : '';
                $lines[] = "- `{$this->shortName($event)}` → handled by `{$listener->shortName}`{$queueTag}";
            }
        }

        // Listeners → notifications (via references)
        foreach ($process->components['listeners'] ?? [] as $listenerFqcn) {
            $listener = $this->findClass($result, $listenerFqcn);

            if ($listener === null) {
                continue;
            }

            foreach ($listener->references as $ref) {
                if (in_array($ref, $process->components['notifications'] ?? [], true)) {
                    $lines[] = "- `{$listener->shortName}` → sends `{$this->shortName($ref)}`";
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $clusteredClassNames
     */
    private function unattached(ProcessMapResult $result, array $clusteredClassNames): string
    {
        $unattached = [];

        foreach ($result->classes as $class) {
            if (in_array($class->type, [ScannerType::Schedule, ScannerType::Broadcast], true)) {
                continue;
            }

            if (in_array($class->className, $clusteredClassNames, true)) {
                continue;
            }

            $unattached[] = $class;
        }

        if ($unattached === []) {
            return '';
        }

        $rows = [
            '| Type | Class | File | Notes |',
            '| --- | --- | --- | --- |',
        ];

        foreach ($unattached as $class) {
            $type = ucfirst($class->type->value);
            $file = $class->filePath !== '' ? '`'.$class->filePath.'`' : '—';
            $notes = $this->notesFor($class);
            $rows[] = "| {$type} | `{$class->className}` | {$file} | {$notes} |";
        }

        return "## Unattached Components\n\n_Classes not bound to a detected process. They may belong to processes the heuristic missed, or be standalone utilities._\n\n".implode("\n", $rows);
    }

    private function routesIndex(ProcessMapResult $result): string
    {
        if ($result->routes === []) {
            return '';
        }

        $rows = [
            '| Method | URI | Name | Controller@method | Middleware |',
            '| --- | --- | --- | --- | --- |',
        ];

        foreach ($result->routes as $route) {
            $rows[] = $this->routeRow($route);
        }

        return "## Routes Index\n\n".implode("\n", $rows);
    }

    private function routeRow(DiscoveredRoute $route): string
    {
        $methods = implode(',', $route->methods);
        $name = $route->name ?? '—';
        $action = $route->controllerClass !== null
            ? '`'.$this->shortName($route->controllerClass).'@'.($route->controllerMethod ?? '').'`'
            : '`'.$route->action.'`';
        $middleware = $route->middleware !== [] ? implode(', ', $route->middleware) : '—';

        return "| {$methods} | /{$route->uri} | {$name} | {$action} | {$middleware} |";
    }

    private function scheduleIndex(ProcessMapResult $result): string
    {
        $tasks = $this->classesOf($result, ScannerType::Schedule);

        if ($tasks === []) {
            return '';
        }

        $rows = [
            '| Cron | Command | Description | Flags |',
            '| --- | --- | --- | --- |',
        ];

        foreach ($tasks as $task) {
            $expr = is_string($task->metadata['expression'] ?? null) ? $task->metadata['expression'] : '—';
            $command = $task->className;
            $description = is_string($task->metadata['description'] ?? null) ? $task->metadata['description'] : '—';

            $flags = [];
            if (($task->metadata['without_overlapping'] ?? false) === true) {
                $flags[] = 'withoutOverlapping';
            }
            if (($task->metadata['on_one_server'] ?? false) === true) {
                $flags[] = 'onOneServer';
            }

            $rows[] = "| `{$expr}` | `{$command}` | {$description} | ".($flags !== [] ? implode(', ', $flags) : '—').' |';
        }

        return "## Scheduled Tasks\n\n".implode("\n", $rows);
    }

    private function broadcastIndex(ProcessMapResult $result): string
    {
        $channels = $this->classesOf($result, ScannerType::Broadcast);

        if ($channels === []) {
            return '';
        }

        $lines = ['## Broadcast Channels', ''];

        foreach ($channels as $channel) {
            $auth = ($channel->metadata['has_authorisation'] ?? false) ? 'with auth callback' : 'no auth callback';
            $lines[] = "- `{$channel->className}` ({$auth})";
        }

        return implode("\n", $lines);
    }

    private function footer(): string
    {
        return <<<'MD'
        ---

        _Read-only snapshot. The package never executes the analysed application's
        business code. Heuristic findings are flagged with hedge wording —
        treat them as investigation hints, not certainties._
        MD;
    }

    /**
     * @return list<string>
     */
    private function clusteredClassNames(ProcessMapResult $result): array
    {
        $names = [];

        foreach ($result->processes as $process) {
            foreach ($process->components as $bucket) {
                foreach ($bucket as $fqcn) {
                    $names[] = $fqcn;
                }
            }
        }

        return array_values(array_unique($names));
    }

    private function findClass(ProcessMapResult $result, string $fqcn): ?DiscoveredClass
    {
        foreach ($result->classes as $class) {
            if ($class->className === $fqcn) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @return list<DiscoveredClass>
     */
    private function classesOf(ProcessMapResult $result, ScannerType $type): array
    {
        return array_values(array_filter(
            $result->classes,
            static fn (DiscoveredClass $c): bool => $c->type === $type,
        ));
    }

    private function shortName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }

    private function roleLabel(string $bucket): string
    {
        return match ($bucket) {
            'models' => 'Model',
            'controllers' => 'Controller',
            'actions' => 'Action',
            'jobs' => 'Job',
            'events' => 'Event',
            'listeners' => 'Listener',
            'notifications' => 'Notification',
            'policies' => 'Policy',
            'commands' => 'Command',
            'schedule' => 'Scheduled task',
            'broadcasts' => 'Broadcast channel',
            default => ucfirst($bucket),
        };
    }

    private function notesFor(DiscoveredClass $class): string
    {
        $bits = [];

        switch ($class->type) {
            case ScannerType::Model:
                $table = is_string($class->metadata['table'] ?? null) ? $class->metadata['table'] : null;
                if ($table !== null) {
                    $bits[] = "table=`{$table}`";
                }
                if (($class->metadata['soft_deletes'] ?? false) === true) {
                    $bits[] = 'softDeletes';
                }
                if (($class->metadata['has_factory'] ?? false) === true) {
                    $bits[] = 'factory';
                }
                if (($class->metadata['timestamps'] ?? true) === false) {
                    $bits[] = 'no timestamps';
                }
                break;

            case ScannerType::Controller:
                if ($class->methods !== []) {
                    $bits[] = 'actions: '.implode('/', $class->methods);
                }
                $forms = $class->metadata['form_requests'] ?? [];
                if (is_array($forms) && $forms !== []) {
                    $bits[] = 'requests: '.count($forms);
                }
                break;

            case ScannerType::Action:
                if (is_string($class->metadata['main_method'] ?? null)) {
                    $bits[] = "entry=`{$class->metadata['main_method']}`";
                }
                $jobs = $class->metadata['dispatches_jobs'] ?? [];
                if (is_array($jobs) && $jobs !== []) {
                    $bits[] = 'dispatches: '.count($jobs);
                }
                $events = $class->metadata['fires_events'] ?? [];
                if (is_array($events) && $events !== []) {
                    $bits[] = 'fires: '.count($events);
                }
                break;

            case ScannerType::Job:
                $bits[] = ($class->metadata['should_queue'] ?? false) === true ? 'queued' : '**sync** (no ShouldQueue)';
                $queue = is_string($class->metadata['queue'] ?? null) ? $class->metadata['queue'] : null;
                if ($queue !== null) {
                    $bits[] = "queue=`{$queue}`";
                }
                if (is_int($class->metadata['tries'] ?? null)) {
                    $bits[] = "tries={$class->metadata['tries']}";
                }
                if (is_int($class->metadata['timeout'] ?? null)) {
                    $bits[] = "timeout={$class->metadata['timeout']}s";
                }
                break;

            case ScannerType::Event:
                if (($class->metadata['should_broadcast'] ?? false) === true) {
                    $bits[] = 'broadcasts';
                }
                if (($class->metadata['serializes_models'] ?? false) === true) {
                    $bits[] = 'serializesModels';
                }
                break;

            case ScannerType::Listener:
                $listensTo = is_string($class->metadata['listens_to'] ?? null) ? $class->metadata['listens_to'] : null;
                if ($listensTo !== null) {
                    $bits[] = 'listens=`'.$this->shortName($listensTo).'`';
                }
                $bits[] = ($class->metadata['should_queue'] ?? false) === true ? 'queued' : 'sync';
                break;

            case ScannerType::Notification:
                $channels = $class->metadata['channels'] ?? [];
                if (is_array($channels) && $channels !== []) {
                    $bits[] = 'channels: '.implode(', ', $channels);
                }
                $bits[] = ($class->metadata['should_queue'] ?? false) === true ? 'queued' : 'sync';
                break;

            case ScannerType::Policy:
                $standard = $class->metadata['standard_abilities'] ?? [];
                if (is_array($standard) && $standard !== []) {
                    $bits[] = 'standard: '.implode('/', $standard);
                }
                $custom = $class->metadata['custom_abilities'] ?? [];
                if (is_array($custom) && $custom !== []) {
                    $bits[] = 'custom: '.implode('/', $custom);
                }
                break;

            case ScannerType::Command:
                if (is_string($class->metadata['signature'] ?? null)) {
                    $bits[] = "`{$class->metadata['signature']}`";
                }
                break;

            default:
                break;
        }

        return implode(' · ', $bits);
    }
}
