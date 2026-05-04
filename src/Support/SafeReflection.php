<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Support;

use ReflectionClass;
use Throwable;

/**
 * Best-effort wrapper around `ReflectionClass`. Used as a fallback for
 * lookups (interfaces, traits, parent class) that AST parsing alone cannot
 * resolve cheaply.
 *
 * Reflection still triggers the autoloader, which in turn may execute side
 * effects in pathological codebases. The package therefore gates this whole
 * helper behind `process-map.safe_reflection.enabled`.
 */
final class SafeReflection
{
    public function __construct(private readonly bool $enabled = true) {}

    /**
     * @return ReflectionClass<object>|null
     */
    public function reflect(string $fqcn): ?ReflectionClass
    {
        if (! $this->enabled || $fqcn === '') {
            return null;
        }

        try {
            return new ReflectionClass($fqcn);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    public function interfaceNames(string $fqcn): array
    {
        $reflection = $this->reflect($fqcn);

        if ($reflection === null) {
            return [];
        }

        try {
            return array_values($reflection->getInterfaceNames());
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    public function traitNames(string $fqcn): array
    {
        $reflection = $this->reflect($fqcn);

        if ($reflection === null) {
            return [];
        }

        try {
            return array_values($reflection->getTraitNames());
        } catch (Throwable) {
            return [];
        }
    }

    public function parentName(string $fqcn): ?string
    {
        $reflection = $this->reflect($fqcn);

        if ($reflection === null) {
            return null;
        }

        try {
            $parent = $reflection->getParentClass();

            return $parent === false ? null : $parent->getName();
        } catch (Throwable) {
            return null;
        }
    }
}
