<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Support\UriTemplate;

#[Title('Process Detail')]
#[Description('Full details of a single process by slug: components, risks, recommendations, bottlenecks.')]
final class ProcessResource extends AbstractProcessMapResource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('process-map://process/{slug}');
    }

    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        $slug = (string) $request->get('slug', '');
        $process = $this->repository->findProcess($slug);

        if ($process === null) {
            return [
                'process' => null,
                'error' => "Process '{$slug}' not found.",
                'suggestion' => 'Use the process-map://processes resource to list available slugs.',
            ];
        }

        return ['process' => $process];
    }
}
