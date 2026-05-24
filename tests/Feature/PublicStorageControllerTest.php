<?php

use Illuminate\Support\Facades\Storage;

it('serves files from the public storage disk via Laravel storage route', function () {
    Storage::fake('public');
    Storage::disk('public')->put('frontend/1/slider/test-hero.jpg', 'fake-image');

    $this->get('/storage/frontend/1/slider/test-hero.jpg')->assertSuccessful();
});

it('returns 404 for missing public storage files', function () {
    Storage::fake('public');

    $this->get('/storage/does-not-exist.jpg')->assertNotFound();
});

it('builds relative storage asset urls', function () {
    expect(storage_asset('frontend/1/photo.jpg'))->toBe('/storage/frontend/1/photo.jpg');
    expect(storage_asset('https://cdn.example.com/x.jpg'))->toBe('https://cdn.example.com/x.jpg');
});

it('does not register a route that serves the private local disk at /storage', function () {
    $routes = collect(app('router')->getRoutes())->map(fn ($r) => $r->getName());

    expect($routes->contains('storage.local'))->toBeFalse();
    expect($routes->contains('storage.public'))->toBeTrue();
});
