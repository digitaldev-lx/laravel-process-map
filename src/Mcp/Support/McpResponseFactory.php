<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Support;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

/**
 * Wraps `Laravel\Mcp\Response` with project-wide envelopes:
 *
 *     { "status": "ok"|"error", "data": ..., "warnings": [], "metadata": {...} }
 *
 * Every tool returns through this factory so the JSON shape stays
 * consistent across the API surface.
 */
final class McpResponseFactory
{
    /**
     * @param  array<string, mixed>|list<mixed>  $data
     * @param  list<string>  $warnings
     * @param  array<string, mixed>  $metadata
     */
    public function ok(array $data, array $warnings = [], array $metadata = []): ResponseFactory
    {
        return Response::structured([
            'status' => 'ok',
            'data' => $data,
            'warnings' => $warnings,
            'metadata' => array_merge([
                'generated_at' => gmdate('c'),
                'source' => 'process-map.json',
            ], $metadata),
        ]);
    }

    /**
     * @param  list<string>  $suggestions
     */
    public function error(string $code, string $message, array $suggestions = []): ResponseFactory
    {
        return Response::structured([
            'status' => 'error',
            'error' => [
                'code' => $code,
                'message' => $message,
                'suggestions' => $suggestions,
            ],
        ]);
    }

    public function processNotFound(string $needle): ResponseFactory
    {
        return $this->error(
            'PROCESS_NOT_FOUND',
            "Process '{$needle}' was not found.",
            ['Use `list_processes` to see available process slugs.'],
        );
    }
}
