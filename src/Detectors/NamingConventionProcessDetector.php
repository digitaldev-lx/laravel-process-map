<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Detectors;

use DigitaldevLx\LaravelProcessMap\Contracts\ProcessDetector;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredProcess;
use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;
use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Support\StrHelpers;

/**
 * Heuristic process detector. Strips verbs and suffixes from class short
 * names, clusters the remaining "entity" parts and emits a process per
 * cluster. The output is always best-effort — exporters surface it with
 * hedging language ("Detected processes (heuristic)").
 *
 * Suffixes are split in two:
 *
 *  - Technical suffixes (`Controller`, `Notification`, `Event`, `Listener`,
 *    `Policy`) are *always* stripped — they describe the artefact, not the
 *    business intent. Configuring them away would defeat the clustering.
 *  - Business suffixes (`Action`, `Job`, `Service`, …) are configurable so
 *    teams can match their conventions.
 */
final class NamingConventionProcessDetector implements ProcessDetector
{
    public const array TECHNICAL_SUFFIXES = [
        'Controller',
        'Notification',
        'Event',
        'Listener',
        'Policy',
    ];

    public const array DEFAULT_BUSINESS_SUFFIXES = [
        'Action',
        'Job',
        'Command',
        'Service',
        'Workflow',
        'Process',
    ];

    public const array DEFAULT_VERBS = [
        'create', 'update', 'delete', 'approve', 'reject', 'publish', 'send', 'notify',
        'import', 'export', 'sync', 'assign', 'complete', 'cancel', 'pay', 'invoice',
        'book', 'schedule', 'generate', 'review', 'moderate',
    ];

    private const array PARTICIPATING_TYPES = [
        ScannerType::Model,
        ScannerType::Action,
        ScannerType::Job,
        ScannerType::Event,
        ScannerType::Listener,
        ScannerType::Notification,
        ScannerType::Controller,
        ScannerType::Command,
        ScannerType::Policy,
        ScannerType::Schedule,
        ScannerType::Broadcast,
    ];

    /**
     * @param  list<string>  $verbs
     * @param  list<string>  $businessSuffixes
     */
    public function __construct(
        private readonly array $verbs = self::DEFAULT_VERBS,
        private readonly array $businessSuffixes = self::DEFAULT_BUSINESS_SUFFIXES,
    ) {}

    /**
     * @return list<DiscoveredProcess>
     */
    public function detect(ProcessMapResult $partial): array
    {
        // Pass 1 — name-based clustering.
        $clusters = [];

        foreach ($partial->classes as $class) {
            if (! in_array($class->type, self::PARTICIPATING_TYPES, true)) {
                continue;
            }

            $entity = $this->extractEntity($class);

            if ($entity === null) {
                continue;
            }

            $clusters[$entity][] = $class;
        }

        // Drop singletons before pass 2 so we only attach orphans to clusters
        // that already have at least two members from the naming heuristic.
        $clusters = array_filter($clusters, static fn (array $classes): bool => count($classes) >= 2);

        // Pass 2 — reference-based attachment. A class that did not land in
        // any cluster is attached to the cluster of any class it references
        // (best-effort: first match wins).
        $clustered = [];

        foreach ($clusters as $entity => $classes) {
            foreach ($classes as $class) {
                $clustered[$class->className] = $entity;
            }
        }

        // Build a reverse lookup of references → which classes reference what.
        $referencedBy = [];

        foreach ($partial->classes as $class) {
            foreach ($class->references as $ref) {
                $referencedBy[$ref][] = $class->className;
            }
        }

        foreach ($partial->classes as $class) {
            if (! in_array($class->type, self::PARTICIPATING_TYPES, true)) {
                continue;
            }

            if (isset($clustered[$class->className])) {
                continue;
            }

            $attached = $this->attachByReferences($class, $clustered, $referencedBy);

            if ($attached !== null) {
                $clusters[$attached][] = $class;
                $clustered[$class->className] = $attached;
            }
        }

        $processes = [];

        foreach ($clusters as $entity => $classes) {
            $processes[] = $this->buildProcess((string) $entity, $classes);
        }

        usort($processes, static fn (DiscoveredProcess $a, DiscoveredProcess $b): int => strcmp($a->name, $b->name));

        return $processes;
    }

    /**
     * @param  array<string, string>  $clustered  className → entity
     * @param  array<string, list<string>>  $referencedBy  className → list of classes that reference it
     */
    private function attachByReferences(DiscoveredClass $class, array $clustered, array $referencedBy): ?string
    {
        // Outgoing: this class references something already in a cluster.
        foreach ($class->references as $reference) {
            if (isset($clustered[$reference])) {
                return $clustered[$reference];
            }
        }

        // Incoming: a class in a cluster references this class.
        foreach ($referencedBy[$class->className] ?? [] as $caller) {
            if (isset($clustered[$caller])) {
                return $clustered[$caller];
            }
        }

        return null;
    }

    private function extractEntity(DiscoveredClass $class): ?string
    {
        $name = $class->shortName;

        // Schedule and Broadcast classes do not carry a short name in the
        // conventional sense — their className IS a command/channel string.
        // Skip clustering for them; they will still appear inside the
        // matching process if the entity hint is present in the metadata.
        if (in_array($class->type, [ScannerType::Schedule, ScannerType::Broadcast], true)) {
            return null;
        }

        // Technical suffixes are stripped first because they are always
        // technical noise (LeadController → Lead, OrderPolicy → Order).
        $stripped = StrHelpers::stripBusinessSuffix($name, self::TECHNICAL_SUFFIXES);
        $stripped = StrHelpers::stripBusinessSuffix($stripped, $this->businessSuffixes);
        $stripped = StrHelpers::stripLeadingVerb($stripped, $this->verbs);

        // Remove obvious "Created", "Updated" event participles.
        foreach (['Created', 'Updated', 'Deleted', 'Approved', 'Rejected', 'Cancelled', 'Sent', 'Assigned', 'Booked'] as $participle) {
            if (str_ends_with($stripped, $participle) && strlen($stripped) > strlen($participle)) {
                $stripped = substr($stripped, 0, -strlen($participle));
                break;
            }
        }

        $stripped = trim($stripped);

        if ($stripped === '' || strlen($stripped) < 3) {
            return null;
        }

        return $stripped;
    }

    /**
     * @param  list<DiscoveredClass>  $classes
     */
    private function buildProcess(string $entity, array $classes): DiscoveredProcess
    {
        $components = ScannerType::emptyComponentBuckets();

        foreach ($classes as $class) {
            $bucket = $class->type->bucket();

            if (! isset($components[$bucket])) {
                continue;
            }

            $components[$bucket][] = $class->className;
        }

        foreach ($components as $key => $value) {
            $components[$key] = array_values(array_unique($value));
        }

        $entityHuman = StrHelpers::humanize($entity);

        return new DiscoveredProcess(
            name: $entityHuman.' Management',
            entity: $entity,
            automationLevel: AutomationLevel::None,
            components: $components,
        );
    }
}
