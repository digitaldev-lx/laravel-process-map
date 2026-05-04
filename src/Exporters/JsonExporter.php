<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Exporters;

use DigitaldevLx\LaravelProcessMap\Data\ProcessMapResult;

final class JsonExporter extends AbstractExporter
{
    /**
     * @param  array{include_method_names?: bool, include_file_paths?: bool}  $privacy
     */
    public function __construct(private readonly array $privacy = []) {}

    public function export(ProcessMapResult $result, ?string $path = null): string
    {
        $payload = $this->applyPrivacy($result->toArray());

        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        if ($path !== null) {
            $this->persist($path, $json."\n");
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyPrivacy(array $payload): array
    {
        $includeMethods = $this->privacy['include_method_names'] ?? true;
        $includeFilePaths = $this->privacy['include_file_paths'] ?? true;

        if ($includeMethods && $includeFilePaths) {
            return $payload;
        }

        if (isset($payload['classes']) && is_array($payload['classes'])) {
            foreach ($payload['classes'] as $i => $class) {
                if (! is_array($class)) {
                    continue;
                }

                if (! $includeMethods) {
                    $class['methods'] = [];
                }

                if (! $includeFilePaths) {
                    $class['file_path'] = '';
                }

                $payload['classes'][$i] = $class;
            }
        }

        return $payload;
    }
}
