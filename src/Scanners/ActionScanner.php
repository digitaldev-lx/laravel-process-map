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

final class ActionScanner extends AbstractAstScanner
{
    private const array MAIN_METHODS = ['handle', 'execute', '__invoke', 'run'];

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
        return ScannerType::Action;
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
        $metadata['main_method'] = $this->detectMainMethod($visitor);
        $metadata['dispatches_jobs'] = $this->filterDispatched($visitor, ['dispatch', 'dispatchSync', 'dispatchNow', 'dispatchAfterResponse', 'dispatch_sync', 'dispatch_now']);
        $metadata['fires_events'] = $this->filterDispatched($visitor, ['event', 'broadcast']);

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

    private function detectMainMethod(ClassMetadataVisitor $visitor): ?string
    {
        foreach (self::MAIN_METHODS as $candidate) {
            if (in_array($candidate, $visitor->publicMethods, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $helpers
     * @return list<string>
     */
    private function filterDispatched(ClassMetadataVisitor $visitor, array $helpers): array
    {
        $matches = [];

        foreach ($visitor->dispatchedClasses as $entry) {
            if (in_array($entry['helper'], $helpers, true) && ! in_array($entry['class'], $matches, true)) {
                $matches[] = $entry['class'];
            }
        }

        return $matches;
    }
}
