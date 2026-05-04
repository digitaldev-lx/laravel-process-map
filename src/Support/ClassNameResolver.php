<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Support;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;

/**
 * Pulls the first declared class' fully qualified name out of a parsed AST.
 * Anonymous classes, traits, interfaces and enums are intentionally ignored —
 * scanners that need them inspect the AST themselves.
 */
final class ClassNameResolver
{
    /**
     * @param  array<int, Node>  $ast
     */
    public function extractClass(array $ast): ?string
    {
        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $namespace = $node->name?->toString();

                foreach ($node->stmts as $child) {
                    if ($child instanceof Class_ && $child->name !== null) {
                        return $namespace !== null && $namespace !== ''
                            ? $namespace.'\\'.$child->name->toString()
                            : $child->name->toString();
                    }
                }

                continue;
            }

            if ($node instanceof Class_ && $node->name !== null) {
                return $node->name->toString();
            }
        }

        return null;
    }

    /**
     * Whether the AST contains a top-level trait declaration. Useful for
     * scanners that want to skip traits (they are not addressable as class
     * dependencies in process flows).
     *
     * @param  array<int, Node>  $ast
     */
    public function declaresTrait(array $ast): bool
    {
        foreach ($ast as $node) {
            if ($node instanceof Trait_) {
                return true;
            }

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $child) {
                    if ($child instanceof Trait_) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
