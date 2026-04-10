<?php

use Illuminate\Support\Facades\Route;

it('registers profile route name', function () {
    expect(Route::has('profile.show'))->toBeTrue();
});

it('registers role middleware aliases', function () {
    $router = app('router');

    /** @var array<string,string> $aliases */
    $aliases = $router->getMiddleware();

    expect($aliases)->toHaveKey('role');
    expect($aliases)->toHaveKey('auth.role');
});
