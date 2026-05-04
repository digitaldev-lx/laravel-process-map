<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;

#[Uri('process-map://recommendations')]
#[Title('Consolidated Recommendations')]
#[Description('All recommendations and bottleneck hints across processes. Bottlenecks are tagged with [bottleneck] in the message.')]
final class RecommendationsResource extends AbstractProcessMapResource
{
    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        $recs = $this->repository->recommendations();

        return ['recommendations' => $recs, 'count' => count($recs)];
    }
}
