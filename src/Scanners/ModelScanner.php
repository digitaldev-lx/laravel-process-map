<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Scanners;

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\Visitors\ClassMetadataVisitor;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;
use SplFileInfo;

final class ModelScanner extends AbstractAstScanner
{
    /**
     * @param  list<string>  $directories
     */
    public function __construct(
        NamespaceResolver $namespaceResolver,
        ClassNameResolver $classNameResolver,
        FileSystemScanner $fileSystemScanner,
        private readonly array $directories,
        ?string $basePath = null,
    ) {
        parent::__construct($namespaceResolver, $classNameResolver, $fileSystemScanner, $basePath);
    }

    public function type(): ScannerType
    {
        return ScannerType::Model;
    }

    /**
     * @return list<string>
     */
    protected function directories(): array
    {
        return $this->directories;
    }

    protected function decorate(DiscoveredClass $class, ClassMetadataVisitor $visitor, SplFileInfo $file, array $ast): DiscoveredClass
    {
        $metadata = $class->metadata;
        $metadata['table'] = $visitor->properties['table'] ?? null;
        $metadata['fillable'] = $this->stringList($visitor->properties['fillable'] ?? null);
        $metadata['guarded'] = $this->stringList($visitor->properties['guarded'] ?? null);
        $metadata['casts'] = $this->stringList($visitor->properties['casts'] ?? null);
        $metadata['timestamps'] = (bool) ($visitor->properties['timestamps'] ?? true);
        $metadata['soft_deletes'] = $this->containsAny($visitor->traits, 'Illuminate\\Database\\Eloquent\\SoftDeletes', 'SoftDeletes');
        $metadata['has_factory'] = $this->containsAny($visitor->traits, 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory', 'HasFactory');
        $metadata['extends_eloquent'] = $visitor->parentClass !== null && (
            $visitor->parentClass === 'Illuminate\\Database\\Eloquent\\Model'
            || str_ends_with($visitor->parentClass, '\\Model')
        );

        return new DiscoveredClass(
            type: $class->type,
            className: $class->className,
            shortName: $class->shortName,
            namespace: $class->namespace,
            filePath: $class->filePath,
            methods: $class->methods,
            traits: $class->traits,
            interfaces: $class->interfaces,
            references: $class->references,
            metadata: $metadata,
        );
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $strings = [];

        foreach ($value as $entry) {
            if (is_string($entry)) {
                $strings[] = $entry;
            }
        }

        return $strings;
    }
}
