<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Scanners;

use DigitaldevLx\LaravelProcessMap\Contracts\Scanner;
use DigitaldevLx\LaravelProcessMap\Data\DiscoveredClass;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\Visitors\ClassMetadataVisitor;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use SplFileInfo;

/**
 * Base class for every class-oriented scanner. Subclasses declare:
 *
 * - `type()`        — the {@see ScannerType} value the scanner emits;
 * - `directories()` — the absolute filesystem locations to walk;
 * - `decorate()`    — optional hook to add scanner-specific metadata to the
 *                     `DiscoveredClass` produced for each file.
 *
 * The base class is responsible for parsing PHP into an AST, extracting
 * class-level metadata via {@see ClassMetadataVisitor}, and assembling the
 * resulting `DiscoveredClass`.
 */
abstract class AbstractAstScanner implements Scanner
{
    public function __construct(
        protected readonly NamespaceResolver $namespaceResolver,
        protected readonly ClassNameResolver $classNameResolver,
        protected readonly FileSystemScanner $fileSystemScanner,
        protected readonly ?string $basePath = null,
    ) {}

    abstract public function type(): ScannerType;

    /**
     * @return list<string>
     */
    abstract protected function directories(): array;

    /**
     * @return list<DiscoveredClass>
     */
    public function scan(): array
    {
        $discovered = [];

        foreach ($this->fileSystemScanner->findPhpFiles($this->directories()) as $file) {
            $class = $this->scanFile($file);

            if ($class !== null) {
                $discovered[] = $class;
            }
        }

        return $discovered;
    }

    protected function scanFile(SplFileInfo $file): ?DiscoveredClass
    {
        $ast = $this->namespaceResolver->parseFile($file->getRealPath() ?: $file->getPathname());

        if ($ast === null) {
            return null;
        }

        if ($this->classNameResolver->declaresTrait($ast)) {
            return null;
        }

        $fqcn = $this->classNameResolver->extractClass($ast);

        if ($fqcn === null) {
            return null;
        }

        $visitor = new ClassMetadataVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        if (! $this->shouldEmit($fqcn, $visitor)) {
            return null;
        }

        $shortName = $this->shortName($fqcn);
        $namespace = $this->namespacePart($fqcn);

        $base = new DiscoveredClass(
            type: $this->type(),
            className: $fqcn,
            shortName: $shortName,
            namespace: $namespace,
            filePath: $this->relativePath($file),
            methods: $visitor->publicMethods,
            traits: $visitor->traits,
            interfaces: $visitor->interfaces,
            references: $visitor->references,
            metadata: $this->baseMetadata($visitor),
        );

        return $this->decorate($base, $visitor, $file, $ast);
    }

    /**
     * Hook for subclasses to extend the metadata array. The already-parsed
     * AST is passed in so subclasses do not need to re-parse the file.
     *
     * @param  array<int, Node>  $ast
     */
    protected function decorate(
        DiscoveredClass $class,
        ClassMetadataVisitor $visitor,
        SplFileInfo $file,
        array $ast,
    ): DiscoveredClass {
        return $class;
    }

    /**
     * Subclasses can opt-out of emitting a class (e.g. abstract classes).
     */
    protected function shouldEmit(string $fqcn, ClassMetadataVisitor $visitor): bool
    {
        return ! $visitor->isAbstract;
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseMetadata(ClassMetadataVisitor $visitor): array
    {
        return [
            'parent_class' => $visitor->parentClass,
            'is_abstract' => $visitor->isAbstract,
            'dispatched_classes' => $visitor->dispatchedClasses,
        ];
    }

    protected function shortName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }

    protected function namespacePart(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? '' : substr($fqcn, 0, $pos);
    }

    /**
     * Make the file path relative to the analysed application's base path
     * when one was supplied. Falls back to the current working directory,
     * which keeps standalone usage from leaking absolute paths into outputs.
     *
     * Both paths are run through `realpath()` so that `__DIR__.'/../foo'`
     * style base paths still match canonical file paths returned by Finder.
     */
    protected function relativePath(SplFileInfo $file): string
    {
        $path = $file->getRealPath() !== false ? $file->getRealPath() : $file->getPathname();

        $rawBase = $this->basePath ?? (getcwd() ?: '');
        $base = $rawBase !== '' ? (realpath($rawBase) ?: rtrim($rawBase, '/\\')) : '';

        if ($base !== '' && str_starts_with($path, $base.DIRECTORY_SEPARATOR)) {
            return substr($path, strlen($base) + 1);
        }

        return $path;
    }

    /**
     * Helper used by subclasses that want to recognise a marker trait/interface
     * (e.g. `Illuminate\Contracts\Queue\ShouldQueue`).
     *
     * @param  list<string>  $haystack
     */
    protected function containsAny(array $haystack, string ...$needles): bool
    {
        foreach ($needles as $needle) {
            foreach ($haystack as $candidate) {
                if ($candidate === $needle || str_ends_with($candidate, '\\'.ltrim($needle, '\\'))) {
                    return true;
                }
            }
        }

        return false;
    }
}
