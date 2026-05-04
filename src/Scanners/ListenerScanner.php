<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Scanners;

use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\Visitors\ClassMetadataVisitor;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use SplFileInfo;

final class ListenerScanner extends AbstractAstScanner
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
        return ScannerType::Listener;
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
        $metadata['listens_to'] = $this->detectListenedEvent($ast);
        $metadata['should_queue'] = $this->containsAny($class->interfaces, 'Illuminate\\Contracts\\Queue\\ShouldQueue', 'ShouldQueue');

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
     * @param  array<int, Node>  $ast
     */
    private function detectListenedEvent(array $ast): ?string
    {
        $finder = new class extends NodeVisitorAbstract
        {
            public ?string $eventClass = null;

            public function enterNode(Node $node): null
            {
                if (! ($node instanceof ClassMethod) || $node->name->toString() !== 'handle') {
                    return null;
                }

                $param = $node->params[0] ?? null;

                if ($param !== null && $param->type instanceof Node\Name) {
                    $this->eventClass = $param->type->toString();
                }

                return null;
            }
        };

        $traverser = new NodeTraverser;
        $traverser->addVisitor($finder);
        $traverser->traverse($ast);

        return $finder->eventClass;
    }
}
