<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\RouteScanner;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/leads', fn () => 'index')->name('leads.index');
    Route::post('/leads', 'Tests\\Fixtures\\App\\Http\\Controllers\\LeadController@store')
        ->name('leads.store')
        ->middleware(['web', 'auth']);
});

it('discovers registered routes with their controller, methods, name and middleware', function (): void {
    $scanner = new RouteScanner(app('router'));

    $routes = $scanner->scan();

    $byName = collect($routes)->keyBy('name');

    expect($byName)->toHaveKeys(['leads.index', 'leads.store']);
    expect($scanner->type())->toBe(ScannerType::Route);

    $store = $byName['leads.store'];

    expect($store->methods)->toContain('POST');
    expect($store->controllerClass)->toBe('Tests\\Fixtures\\App\\Http\\Controllers\\LeadController');
    expect($store->controllerMethod)->toBe('store');
    expect($store->middleware)->toContain('web', 'auth');
});

it('skips routes whose URI starts with an ignored prefix (M1)', function (): void {
    Route::get('/telescope/dashboard', fn () => 'tools');
    Route::get('/horizon/api/stats', fn () => 'tools');

    $scanner = new RouteScanner(app('router'), ['telescope', 'horizon']);

    $names = array_map(static fn ($r) => $r->uri, $scanner->scan());

    foreach ($names as $uri) {
        expect($uri)->not->toStartWith('telescope');
        expect($uri)->not->toStartWith('horizon');
    }
});

it('flags closure routes with action "Closure"', function (): void {
    $scanner = new RouteScanner(app('router'));

    $closure = collect($scanner->scan())->firstWhere('name', 'leads.index');

    expect($closure->action)->toBe('Closure');
    expect($closure->controllerClass)->toBeNull();
});
