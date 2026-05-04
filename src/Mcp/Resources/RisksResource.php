<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;

#[Uri('process-map://risks')]
#[Title('Consolidated Risks')]
#[Description('All risks flagged across all processes, with the originating process slug.')]
final class RisksResource extends AbstractProcessMapResource
{
    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        $risks = $this->repository->risks();

        return ['risks' => $risks, 'count' => count($risks)];
    }
}
