<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Scanners;

use DigitaldevLx\LaravelProcessMap\Contracts\Scanner;
use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Parses `routes/channels.php` (or any configured equivalent) for
 * `Broadcast::channel('name', ...)` calls. Only the channel name and the
 * presence of an authorisation callback are recorded — the callback itself
 * is never executed.
 */
final class BroadcastScanner implements Scanner
{
    /**
     * @param  list<string>  $files
     */
    public function __construct(
        private readonly NamespaceResolver $namespaceResolver,
        private readonly array $files,
    ) {}

    public function type(): ScannerType
    {
        return ScannerType::Broadcast;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function scan(): array
    {
        $channels = [];

        foreach ($this->files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $ast = $this->namespaceResolver->parseFile($file);

            if ($ast === null) {
                continue;
            }

            foreach ($this->extractChannels($ast) as $channel) {
                $channels[] = $channel + ['file' => $file];
            }
        }

        return $channels;
    }

    /**
     * @param  array<int, Node>  $ast
     * @return list<array<string, mixed>>
     */
    private function extractChannels(array $ast): array
    {
        $visitor = new class extends NodeVisitorAbstract
        {
            /** @var list<array<string, mixed>> */
            public array $channels = [];

            public function enterNode(Node $node): null
            {
                if (! ($node instanceof StaticCall) || ! ($node->name instanceof Identifier)) {
                    return null;
                }

                if ($node->name->toString() !== 'channel' || ! ($node->class instanceof Name)) {
                    return null;
                }

                $facade = $node->class->toString();

                if (! str_ends_with($facade, 'Broadcast')) {
                    return null;
                }

                $first = $node->args[0]->value ?? null;

                if (! ($first instanceof String_)) {
                    return null;
                }

                $this->channels[] = [
                    'name' => $first->value,
                    'has_authorisation' => isset($node->args[1]),
                ];

                return null;
            }
        };

        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->channels;
    }
}
