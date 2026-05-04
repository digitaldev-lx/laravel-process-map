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

final class PolicyScanner extends AbstractAstScanner
{
    private const array STANDARD_ABILITIES = [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
        'restore',
        'forceDelete',
        'before',
    ];

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
        return ScannerType::Policy;
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
        $metadata['standard_abilities'] = array_values(array_intersect(self::STANDARD_ABILITIES, $class->methods));
        $metadata['custom_abilities'] = array_values(array_diff(
            array_filter($class->methods, static fn (string $m): bool => ! str_starts_with($m, '__')),
            self::STANDARD_ABILITIES,
        ));
        $metadata['model'] = $this->guessModel($class);

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
     * Best-effort: a policy class named `LeadPolicy` is conventionally bound
     * to the `Lead` model. Returns the bare model short name; resolving the
     * full FQCN is the application's job (auth provider mapping).
     */
    private function guessModel(DiscoveredClass $class): ?string
    {
        if (! str_ends_with($class->shortName, 'Policy')) {
            return null;
        }

        $bare = substr($class->shortName, 0, -strlen('Policy'));

        return $bare === '' ? null : $bare;
    }
}
