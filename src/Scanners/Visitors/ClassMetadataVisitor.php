<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Scanners\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;

/**
 * Extracts metadata from the first class declaration encountered in an AST:
 * extended parent, implemented interfaces, used traits, public method names,
 * and references to other classes (`new Foo`, `Foo::bar()`, `dispatch(new
 * Foo)`, `event(new Foo)`).
 *
 * The visitor is single-class focused — once it has filled its buckets, the
 * caller can read them off via the public properties. Re-use a fresh
 * instance per file to avoid contamination.
 */
final class ClassMetadataVisitor extends NodeVisitorAbstract
{
    public ?string $parentClass = null;

    /** @var list<string> */
    public array $interfaces = [];

    /** @var list<string> */
    public array $traits = [];

    /** @var list<string> */
    public array $publicMethods = [];

    /** @var list<string> */
    public array $allMethods = [];

    /** @var list<string> */
    public array $references = [];

    /** @var list<array{method: string, class: string|null}> */
    public array $staticCalls = [];

    /** @var list<array{helper: string, class: string}> */
    public array $dispatchedClasses = [];

    /** @var array<string, mixed> */
    public array $properties = [];

    public bool $isAbstract = false;

    public function enterNode(Node $node): null
    {
        if ($node instanceof Class_) {
            $this->collectClass($node);
        }

        if ($node instanceof StaticCall) {
            $this->collectStaticCall($node);
        }

        if ($node instanceof New_) {
            $this->collectNew($node);
        }

        if ($node instanceof FuncCall) {
            $this->collectFuncCall($node);
        }

        return null;
    }

    private function collectClass(Class_ $node): void
    {
        if ($node->isAbstract()) {
            $this->isAbstract = true;
        }

        if ($node->extends instanceof Name) {
            $this->parentClass = $node->extends->toString();
        }

        foreach ($node->implements as $interface) {
            $this->interfaces[] = $interface->toString();
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $this->traits[] = $trait->toString();
                }
            }

            if ($stmt instanceof ClassMethod) {
                $name = $stmt->name->toString();
                $this->allMethods[] = $name;

                if ($stmt->isPublic()) {
                    $this->publicMethods[] = $name;
                }

                foreach ($stmt->params as $param) {
                    $this->collectParamType($param);
                }
            }

            if ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    $this->properties[$prop->name->toString()] = $this->resolvePropertyDefault($prop);
                }
            }
        }
    }

    private function collectParamType(Node\Param $param): void
    {
        $type = $param->type;

        if ($type instanceof Node\NullableType) {
            $type = $type->type;
        }

        if ($type instanceof Name) {
            $this->reference($type->toString());

            return;
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            foreach ($type->types as $inner) {
                if ($inner instanceof Name) {
                    $this->reference($inner->toString());
                }
            }
        }
    }

    private function collectStaticCall(StaticCall $node): void
    {
        if (! ($node->class instanceof Name) || ! ($node->name instanceof Node\Identifier)) {
            return;
        }

        $class = $node->class->toString();
        $method = $node->name->toString();

        $this->staticCalls[] = ['method' => $method, 'class' => $class];
        $this->reference($class);

        // Job::dispatch() / Job::dispatchSync() — the dispatched class is the
        // facade itself.
        if (in_array($method, ['dispatch', 'dispatchSync', 'dispatchNow', 'dispatchAfterResponse'], true)) {
            $this->dispatchedClasses[] = ['helper' => $method, 'class' => $class];
        }

        // Bus::dispatch(new Job) / Bus::dispatchSync(new Job) — the dispatched
        // class is the first argument's `new` expression.
        if (str_ends_with($class, 'Bus') && in_array($method, ['dispatch', 'dispatchSync', 'dispatchNow', 'dispatchAfterResponse'], true)) {
            $this->captureNewArgument($node->args[0] ?? null, $method);
        }
    }

    private function captureNewArgument(?Node\Arg $arg, string $helper): void
    {
        if ($arg === null) {
            return;
        }

        $value = $arg->value;

        if ($value instanceof New_ && $value->class instanceof Name) {
            $class = $value->class->toString();
            $this->dispatchedClasses[] = ['helper' => $helper, 'class' => $class];
            $this->reference($class);
        }
    }

    private function collectNew(New_ $node): void
    {
        if ($node->class instanceof Name) {
            $this->reference($node->class->toString());
        }
    }

    private function collectFuncCall(FuncCall $node): void
    {
        if (! ($node->name instanceof Name)) {
            return;
        }

        $helper = $node->name->toString();

        if (! in_array($helper, ['dispatch', 'dispatch_sync', 'dispatch_now', 'event', 'broadcast', 'report'], true)) {
            return;
        }

        foreach ($node->args as $arg) {
            $value = $arg->value;

            if ($value instanceof New_ && $value->class instanceof Name) {
                $class = $value->class->toString();
                $this->dispatchedClasses[] = ['helper' => $helper, 'class' => $class];
                $this->reference($class);
            }
        }
    }

    private function reference(string $class): void
    {
        if ($class === '' || in_array($class, $this->references, true)) {
            return;
        }

        if (in_array($class, ['self', 'static', 'parent'], true)) {
            return;
        }

        $this->references[] = $class;
    }

    private function resolvePropertyDefault(Node\PropertyItem $item): mixed
    {
        if ($item->default === null) {
            return null;
        }

        if ($item->default instanceof Node\Scalar\String_) {
            return $item->default->value;
        }

        if ($item->default instanceof Node\Scalar\Int_) {
            return $item->default->value;
        }

        if ($item->default instanceof Node\Expr\ConstFetch) {
            return $item->default->name->toLowerString();
        }

        if ($item->default instanceof Node\Expr\Array_) {
            return array_values(array_filter(array_map(
                static function (?Node\Expr\ArrayItem $arrayItem): mixed {
                    if ($arrayItem === null || $arrayItem->value instanceof Node\Expr\Array_) {
                        return null;
                    }

                    if ($arrayItem->value instanceof Node\Scalar\String_) {
                        return $arrayItem->value->value;
                    }

                    if ($arrayItem->value instanceof Node\Scalar\Int_) {
                        return $arrayItem->value->value;
                    }

                    return null;
                },
                $item->default->items,
            ), static fn (mixed $v): bool => $v !== null));
        }

        return null;
    }
}
