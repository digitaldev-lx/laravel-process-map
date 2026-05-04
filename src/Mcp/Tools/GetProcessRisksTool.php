<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Get process risks')]
#[Description('Returns the risks (and bottlenecks) flagged for a single process.')]
#[IsReadOnly]
final class GetProcessRisksTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'process' => $schema->string()->description('Process slug, name or entity.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $needle = (string) $request->get('process', '');
        $process = $this->repository->findProcess($needle);

        if ($process === null) {
            $this->processNotFound($needle);
        }

        return [
            'process' => $process['name'] ?? null,
            'slug' => $process['slug'] ?? null,
            'risks' => array_values((array) ($process['risks'] ?? [])),
            'potential_bottlenecks' => array_values((array) ($process['potential_bottlenecks'] ?? [])),
        ];
    }
}
