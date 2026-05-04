<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;

#[Uri('process-map://summary')]
#[Title('Process Map Summary')]
#[Description('High-level summary: app metadata, generation timestamp, schema version and totals.')]
final class SummaryResource extends AbstractProcessMapResource
{
    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        return $this->repository->summary();
    }
}
