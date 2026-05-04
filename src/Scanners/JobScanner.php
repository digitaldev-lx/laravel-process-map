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

final class JobScanner extends AbstractAstScanner
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
        return ScannerType::Job;
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
        $metadata['should_queue'] = $this->containsAny($visitor->interfaces, 'Illuminate\\Contracts\\Queue\\ShouldQueue', 'ShouldQueue');
        $metadata['queue'] = $this->stringProperty($visitor, 'queue');
        $metadata['tries'] = $this->intProperty($visitor, 'tries');
        $metadata['backoff'] = $this->intProperty($visitor, 'backoff');
        $metadata['timeout'] = $this->intProperty($visitor, 'timeout');

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

    private function stringProperty(ClassMetadataVisitor $visitor, string $name): ?string
    {
        $value = $visitor->properties[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    private function intProperty(ClassMetadataVisitor $visitor, string $name): ?int
    {
        $value = $visitor->properties[$name] ?? null;

        return is_int($value) ? $value : null;
    }
}
