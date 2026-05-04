<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->parser = new NamespaceResolver;
    $this->resolver = new ClassNameResolver;
});

it('extracts the FQCN of a namespaced class', function (): void {
    $ast = $this->parser->parseSource(<<<'PHP'
        <?php
        namespace App\Models;
        class Lead {}
        PHP);

    expect($this->resolver->extractClass($ast))->toBe('App\\Models\\Lead');
});

it('extracts a class declared without a namespace', function (): void {
    $ast = $this->parser->parseSource(<<<'PHP'
        <?php
        class GlobalThing {}
        PHP);

    expect($this->resolver->extractClass($ast))->toBe('GlobalThing');
});

it('returns null when the file declares only a trait', function (): void {
    $ast = $this->parser->parseSource(<<<'PHP'
        <?php
        namespace App\Concerns;
        trait HasUuid {}
        PHP);

    expect($this->resolver->extractClass($ast))->toBeNull();
    expect($this->resolver->declaresTrait($ast))->toBeTrue();
});

it('returns null for invalid PHP source', function (): void {
    $ast = $this->parser->parseSource('<?php class { broken');

    expect($ast)->toBeNull();
});
