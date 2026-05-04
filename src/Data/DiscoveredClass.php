<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Data;

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;

final class DiscoveredClass
{
    /**
     * @param  list<string>  $methods
     * @param  list<string>  $traits
     * @param  list<string>  $interfaces
     * @param  list<string>  $references
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly ScannerType $type,
        public readonly string $className,
        public readonly string $shortName,
        public readonly string $namespace,
        public readonly string $filePath,
        public readonly array $methods = [],
        public readonly array $traits = [],
        public readonly array $interfaces = [],
        public readonly array $references = [],
        public readonly array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'class_name' => $this->className,
            'short_name' => $this->shortName,
            'namespace' => $this->namespace,
            'file_path' => $this->filePath,
            'methods' => $this->methods,
            'traits' => $this->traits,
            'interfaces' => $this->interfaces,
            'references' => $this->references,
            'metadata' => $this->metadata,
        ];
    }
}
