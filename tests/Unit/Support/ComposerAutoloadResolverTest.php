<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Exceptions\ProcessMapException;
use DigitaldevLx\LaravelProcessMap\Support\ComposerAutoloadResolver;

beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/process-map-resolver-'.uniqid();
    mkdir($this->base.'/app/Models', 0777, true);
    mkdir($this->base.'/src/Domain', 0777, true);
    mkdir($this->base.'/tests', 0777, true);

    file_put_contents($this->base.'/composer.json', json_encode([
        'autoload' => [
            'psr-4' => [
                'App\\' => 'app/',
                'Acme\\Domain\\' => ['src/Domain/'],
            ],
        ],
        'autoload-dev' => [
            'psr-4' => [
                'Tests\\' => 'tests/',
            ],
        ],
    ], JSON_THROW_ON_ERROR));
});

afterEach(function (): void {
    exec('rm -rf '.escapeshellarg($this->base));
});

it('reads psr-4 mappings from autoload and autoload-dev', function (): void {
    $resolver = new ComposerAutoloadResolver($this->base);

    $mappings = $resolver->psr4Mappings();

    expect($mappings)->toHaveKeys(['App\\', 'Acme\\Domain\\', 'Tests\\']);
});

it('resolves a path inside an autoload root to its FQCN', function (): void {
    file_put_contents($this->base.'/app/Models/Lead.php', '<?php');
    file_put_contents($this->base.'/src/Domain/Booking.php', '<?php');
    file_put_contents($this->base.'/tests/HelperTest.php', '<?php');

    $resolver = new ComposerAutoloadResolver($this->base);

    expect($resolver->resolveClassFromPath($this->base.'/app/Models/Lead.php'))
        ->toBe('App\\Models\\Lead');

    expect($resolver->resolveClassFromPath($this->base.'/src/Domain/Booking.php'))
        ->toBe('Acme\\Domain\\Booking');

    expect($resolver->resolveClassFromPath($this->base.'/tests/HelperTest.php'))
        ->toBe('Tests\\HelperTest');
});

it('returns null for a path outside any autoload root', function (): void {
    file_put_contents($this->base.'/orphan.php', '<?php');

    $resolver = new ComposerAutoloadResolver($this->base);

    expect($resolver->resolveClassFromPath($this->base.'/orphan.php'))->toBeNull();
});

it('throws when the composer file is missing', function (): void {
    $empty = sys_get_temp_dir().'/process-map-resolver-empty-'.uniqid();
    mkdir($empty);

    expect(fn () => new ComposerAutoloadResolver($empty))
        ->toThrow(ProcessMapException::class);

    rmdir($empty);
});
