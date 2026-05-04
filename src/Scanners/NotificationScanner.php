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
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use SplFileInfo;

final class NotificationScanner extends AbstractAstScanner
{
    /**
     * Whitelist of well-known Laravel notification channels. Stops methods
     * like `toString`/`toArray` from being misreported as delivery channels.
     */
    private const array KNOWN_DELIVERY_METHODS = [
        'toMail',
        'toDatabase',
        'toBroadcast',
        'toVonage',
        'toSms',
        'toSlack',
        'toMicrosoftTeams',
        'toFcm',
        'toApn',
        'toWebPush',
        'toTwilio',
        'toNexmo',
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
        return ScannerType::Notification;
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
        $metadata['channels'] = $this->extractChannels($ast);
        $metadata['should_queue'] = $this->containsAny($class->interfaces, 'Illuminate\\Contracts\\Queue\\ShouldQueue', 'ShouldQueue');
        $metadata['delivery_methods'] = array_values(array_intersect(self::KNOWN_DELIVERY_METHODS, $class->methods));

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
     * @return list<string>
     */
    private function extractChannels(array $ast): array
    {
        $finder = new class extends NodeVisitorAbstract
        {
            /** @var list<string> */
            public array $channels = [];

            public function enterNode(Node $node): null
            {
                if (! ($node instanceof ClassMethod) || $node->name->toString() !== 'via') {
                    return null;
                }

                foreach ($node->stmts ?? [] as $stmt) {
                    if (! ($stmt instanceof Return_) || ! ($stmt->expr instanceof Array_)) {
                        continue;
                    }

                    foreach ($stmt->expr->items as $item) {
                        if ($item !== null && $item->value instanceof String_) {
                            $this->channels[] = $item->value->value;
                        }
                    }
                }

                return null;
            }
        };

        $traverser = new NodeTraverser;
        $traverser->addVisitor($finder);
        $traverser->traverse($ast);

        return array_values(array_unique($finder->channels));
    }
}
