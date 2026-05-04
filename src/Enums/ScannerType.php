<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Enums;

/**
 * The kind of class a scanner discovered. Drives both the JSON shape and the
 * way detectors group classes into processes.
 */
enum ScannerType: string
{
    case Model = 'model';
    case Controller = 'controller';
    case Action = 'action';
    case Job = 'job';
    case Event = 'event';
    case Listener = 'listener';
    case Notification = 'notification';
    case Policy = 'policy';
    case Command = 'command';
    case Route = 'route';
    case Schedule = 'schedule';
    case Broadcast = 'broadcast';

    /**
     * Stable bucket key used by detectors and exporters to group classes of
     * this type. Hand-rolled to avoid the naive `value.'s'` trap (e.g.
     * `policy` → `policys`).
     */
    public function bucket(): string
    {
        return match ($this) {
            self::Model => 'models',
            self::Controller => 'controllers',
            self::Action => 'actions',
            self::Job => 'jobs',
            self::Event => 'events',
            self::Listener => 'listeners',
            self::Notification => 'notifications',
            self::Policy => 'policies',
            self::Command => 'commands',
            self::Route => 'routes',
            self::Schedule => 'schedule',
            self::Broadcast => 'broadcasts',
        };
    }

    /**
     * Buckets used by the process detector when assembling a process. Routes
     * are intentionally left out — they are tracked at the result level.
     *
     * @return array<string, list<string>>
     */
    public static function emptyComponentBuckets(): array
    {
        return [
            self::Model->bucket() => [],
            self::Controller->bucket() => [],
            self::Action->bucket() => [],
            self::Job->bucket() => [],
            self::Event->bucket() => [],
            self::Listener->bucket() => [],
            self::Notification->bucket() => [],
            self::Policy->bucket() => [],
            self::Command->bucket() => [],
            self::Schedule->bucket() => [],
            self::Broadcast->bucket() => [],
        ];
    }
}
