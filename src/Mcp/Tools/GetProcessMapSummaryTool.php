<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Get process map summary')]
#[Description('Returns the high-level summary of the application: app metadata, totals (models, controllers, ...), generation timestamp and schema version.')]
#[IsReadOnly]
final class GetProcessMapSummaryTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        return $this->repository->summary();
    }
}
