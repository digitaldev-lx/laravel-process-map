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

final class ControllerScanner extends AbstractAstScanner
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
        return ScannerType::Controller;
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
        $metadata['action_endpoints'] = array_values(array_filter(
            $class->methods,
            static fn (string $method): bool => ! str_starts_with($method, '__'),
        ));
        $metadata['form_requests'] = $this->detectFormRequests($visitor);

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
     * Walks the references collected by the visitor and keeps the ones that
     * look like Laravel form requests (FQCN ending in `Request`).
     *
     * @return list<string>
     */
    private function detectFormRequests(ClassMetadataVisitor $visitor): array
    {
        return array_values(array_unique(array_filter(
            $visitor->references,
            static fn (string $ref): bool => str_ends_with($ref, 'Request'),
        )));
    }
}
