<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use DigitaldevLx\LaravelProcessMap\Mcp\Support\McpSecurityGuard;
use DigitaldevLx\LaravelProcessMap\Mcp\Support\ProcessMapRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

abstract class AbstractProcessMapTool extends Tool
{
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
                'data' => $this->run($request),
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
        } catch (Throwable $e) {
            $payload = [
                'status' => 'error',
                'error' => [
                    'code' => 'INTERNAL_ERROR',
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
    abstract protected function run(Request $request): array;

    protected function processNotFound(string $needle): never
    {
        throw new ProcessMapException(
            "Process '{$needle}' was not found. Use the `list_processes` tool to see available slugs."
        );
    }
}
