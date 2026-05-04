<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Support;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Parses a PHP source file into an AST with fully qualified names already
 * resolved. Wraps `nikic/php-parser` so that the rest of the package never
 * imports the parser library directly.
 */
final class NamespaceResolver
{
    private readonly Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForHostVersion();
    }

    /**
     * Parse a file's contents into an AST with FQCNs already resolved on
     * every node (`Name::getAttribute('resolvedName')`).
     *
     * @return array<int, Node>|null null when the file cannot be parsed
     */
    public function parseFile(string $absolutePath): ?array
    {
        if (! is_readable($absolutePath)) {
            return null;
        }

        $source = file_get_contents($absolutePath);

        if ($source === false || $source === '') {
            return null;
        }

        return $this->parseSource($source);
    }

    /**
     * @return array<int, Node>|null
     */
    public function parseSource(string $source): ?array
    {
        try {
            $ast = $this->parser->parse($source);
        } catch (\Throwable) {
            return null;
        }

        if ($ast === null) {
            return null;
        }

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);

        return $traverser->traverse($ast);
    }
}
