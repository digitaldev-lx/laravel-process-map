<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Mcp\Support\ProcessMapSanitizer;
use Illuminate\Config\Repository as ConfigRepository;

function sanitizer(bool $redact = true): ProcessMapSanitizer
{
    return new ProcessMapSanitizer(new ConfigRepository([
        'process-map' => [
            'mcp' => ['security' => ['redact_sensitive_values' => $redact]],
        ],
    ]));
}

it('redacts string values whose key looks sensitive', function (): void {
    $payload = [
        'app' => ['name' => 'Demo'],
        'config' => [
            'database_password' => 'super-secret',
            'api_key' => 'sk-1234',
            'authorization' => 'Bearer xyz',
            'connection' => 'mysql://user:pass@host/db',
            'public_url' => 'https://example.com',
        ],
        'classes' => [
            ['class_name' => 'App\\Models\\User', 'metadata' => ['secret_token' => 'abc']],
        ],
    ];

    $clean = sanitizer()->sanitise($payload);

    expect($clean['config']['database_password'])->toBe('[REDACTED]');
    expect($clean['config']['api_key'])->toBe('[REDACTED]');
    expect($clean['config']['authorization'])->toBe('[REDACTED]');
    expect($clean['config']['connection'])->toBe('[REDACTED]');
    expect($clean['config']['public_url'])->toBe('https://example.com');
    expect($clean['classes'][0]['metadata']['secret_token'])->toBe('[REDACTED]');
});

it('returns the payload unchanged when redaction is disabled', function (): void {
    $payload = ['config' => ['password' => 'secret']];

    expect(sanitizer(redact: false)->sanitise($payload))->toBe($payload);
});

it('skips non-string values even when the key matches', function (): void {
    $payload = ['secret' => ['nested' => 'value']];

    $clean = sanitizer()->sanitise($payload);

    // Nested array under "secret" is recursed, not blanket-redacted.
    expect($clean['secret'])->toBe(['nested' => 'value']);
});
