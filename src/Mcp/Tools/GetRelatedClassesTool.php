<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Get related classes')]
#[Description('Returns the classes that reference the given class up to a bounded depth (max clamped at process-map.mcp.tools.max_related_depth).')]
#[IsReadOnly]
final class GetRelatedClassesTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'class' => $schema->string()->description('Fully qualified class name.')->required(),
            'depth' => $schema->integer()->description('Recursion depth (clamped to package limits).')->default(1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $target = (string) $request->get('class', '');
        $depth = max(1, min($this->guard->maxRelatedDepth(), (int) $request->get('depth', 1)));

        if ($target === '') {
            throw new ProcessMapException('Argument `class` is required.');
        }

        $byName = [];
        foreach ($this->repository->classes() as $class) {
            $name = (string) ($class['class_name'] ?? '');
            if ($name !== '') {
                $byName[$name] = $class;
            }
        }

        if (! isset($byName[$target])) {
            return [
                'class' => $target,
                'related' => [],
                'note' => 'Class not present in the snapshot.',
            ];
        }

        $visited = [$target => 0];
        $frontier = [$target];

        for ($level = 1; $level <= $depth; $level++) {
            $next = [];

            foreach ($frontier as $current) {
                $references = (array) (($byName[$current] ?? [])['references'] ?? []);

                foreach ($references as $ref) {
                    if (! is_string($ref) || isset($visited[$ref])) {
                        continue;
                    }

                    if (isset($byName[$ref])) {
                        $visited[$ref] = $level;
                        $next[] = $ref;
                    }
                }

                foreach ($byName as $name => $class) {
                    if (isset($visited[$name])) {
                        continue;
                    }
                    $refs = (array) ($class['references'] ?? []);
                    if (in_array($current, $refs, true)) {
                        $visited[$name] = $level;
                        $next[] = $name;
                    }
                }
            }

            $frontier = $next;

            if ($frontier === []) {
                break;
            }
        }

        unset($visited[$target]);

        $related = [];

        foreach ($visited as $fqcn => $reachedAt) {
            $related[] = [
                'class' => $fqcn,
                'type' => $byName[$fqcn]['type'] ?? null,
                'depth' => $reachedAt,
                'file' => $byName[$fqcn]['file_path'] ?? null,
            ];
        }

        return [
            'class' => $target,
            'effective_depth' => $depth,
            'related' => $related,
            'count' => count($related),
        ];
    }
}
