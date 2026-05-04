<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Get process details')]
#[Description('Returns the full detail of one process: components, risks, recommendations and bottlenecks. Accepts slug, exact name or entity (case-insensitive).')]
#[IsReadOnly]
final class GetProcessDetailsTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'process' => $schema->string()
                ->description('Process slug, name or entity.')
                ->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $needle = (string) $request->get('process', '');

        if ($needle === '') {
            throw new ProcessMapException(
                'Argument `process` is required.'
            );
        }

        $process = $this->repository->findProcess($needle);

        if ($process === null) {
            $this->processNotFound($needle);
        }

        return ['process' => $process];
    }
}
