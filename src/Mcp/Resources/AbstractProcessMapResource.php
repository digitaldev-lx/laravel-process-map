<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Resources;

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use DigitaldevLx\LaravelProcessMap\Mcp\Support\McpSecurityGuard;
use DigitaldevLx\LaravelProcessMap\Mcp\Support\ProcessMapRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

/**
 * Shared boilerplate for every read-only resource served by the MCP layer.
 * Children only have to implement `data(Request $request): array`.
 *
 * The output is always JSON-encoded text, so MCP clients can either
 * pass it straight to the model or parse it into native objects. Errors
 * are returned as a JSON object with a `status: "error"` envelope so the
 * client gets a uniform response shape.
 */
abstract class AbstractProcessMapResource extends Resource
{
    protected string $mimeType = 'application/json';

    public function __construct(
        protected readonly ProcessMapRepository $repository,
        protected readonly McpSecurityGuard $guard,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $this->guard->ensureMcpEnabled();
            $payload = [
                'status' => 'ok',
                'data' => $this->data($request),
                'metadata' => [
                    'generated_at' => gmdate('c'),
                    'source' => basename($this->repository->path()),
                ],
            ];
        } catch (ProcessMapException $e) {
            $payload = [
                'status' => 'error',
                'error' => [
                    'code' => 'PROCESS_MAP_ERROR',
                    'message' => $e->getMessage(),
                ],
            ];
        }

        return Response::text((string) json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function data(Request $request): array;
}
