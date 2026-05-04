<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;

#[Uri('process-map://mermaid')]
#[MimeType('text/markdown')]
#[Title('Mermaid Diagram (full)')]
#[Description('The full Mermaid flowchart of the discovered process map. Returns the raw .mmd content.')]
final class MermaidResource extends AbstractProcessMapResource
{
    public function handle(Request $request): Response
    {
        try {
            $this->guard->ensureMcpEnabled();
            $path = $this->repository->mermaidPath();

            if (! is_file($path)) {
                return Response::text('%% No mermaid file found. Run `php artisan process-map:scan --mermaid` first.');
            }

            $contents = file_get_contents($path);

            return Response::text($contents !== false ? $contents : '%% Mermaid file is unreadable.');
        } catch (\Throwable $e) {
            return Response::text('%% '.$e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        return [];
    }
}
