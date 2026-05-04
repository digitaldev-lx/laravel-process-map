<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Support;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * Walks the snapshot tree and redacts values whose keys look sensitive.
 * The check is heuristic — defence in depth — because the upstream JSON
 * already excludes property values and docblocks. Belt + suspenders.
 */
final class ProcessMapSanitizer
{
    public const string REDACTED = '[REDACTED]';

    /**
     * @var list<string> lower-cased substrings that mark a key as sensitive
     */
    private const array SENSITIVE_TOKENS = [
        'password',
        'secret',
        'token',
        'api_key',
        'apikey',
        'authorization',
        'bearer',
        'credential',
        'connection',
        'database_password',
        'mail_password',
        'stripe_secret',
        'aws_secret',
        'private_key',
    ];

    public function __construct(private readonly Config $config) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitise(array $payload): array
    {
        if (! (bool) $this->config->get('process-map.mcp.security.redact_sensitive_values', true)) {
            return $payload;
        }

        return $this->walk($payload);
    }

    /**
     * @param  array<int|string, mixed>  $value
     * @return array<int|string, mixed>
     */
    private function walk(array $value): array
    {
        foreach ($value as $key => $sub) {
            if (is_string($key) && $this->isSensitiveKey($key) && is_string($sub)) {
                $value[$key] = self::REDACTED;

                continue;
            }

            if (is_array($sub)) {
                $value[$key] = $this->walk($sub);
            }
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $lower = strtolower($key);

        foreach (self::SENSITIVE_TOKENS as $token) {
            if (str_contains($lower, $token)) {
                return true;
            }
        }

        return false;
    }
}
