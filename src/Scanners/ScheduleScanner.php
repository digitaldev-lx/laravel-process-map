<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Scanners;

use DigitaldevLx\LaravelProcessMap\Contracts\Scanner;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Throwable;

/**
 * Best-effort scanner for scheduled tasks. Pulls the registered events from
 * Laravel's Schedule and serialises a small subset of their metadata.
 *
 * The scanner does not run any of the schedule callbacks — it only inspects
 * the registered Event instances via public APIs (`getExpression`,
 * `command`, `description`).
 */
final class ScheduleScanner implements Scanner
{
    public function __construct(private readonly Schedule $schedule) {}

    public function type(): ScannerType
    {
        return ScannerType::Schedule;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function scan(): array
    {
        $entries = [];

        try {
            $events = $this->schedule->events();
        } catch (Throwable) {
            return [];
        }

        foreach ($events as $event) {
            $entries[] = $this->describe($event);
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(Event $event): array
    {
        return [
            'expression' => $event->getExpression(),
            'description' => $event->description,
            'command' => $event->command,
            'mutex_name' => $this->mutexName($event),
            'timezone' => $event->timezone instanceof \DateTimeZone
                ? $event->timezone->getName()
                : (is_string($event->timezone) ? $event->timezone : null),
            'without_overlapping' => $event->withoutOverlapping,
            'on_one_server' => $event->onOneServer,
        ];
    }

    private function mutexName(Event $event): ?string
    {
        try {
            return $event->mutexName();
        } catch (Throwable) {
            return null;
        }
    }
}
