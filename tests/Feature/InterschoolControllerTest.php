<?php

use Illuminate\Support\Facades\Route;

it('registers interschool season update route with put method', function () {
    $updateSeasonRoute = collect(Route::getRoutes())->first(function ($route) {
        return str_contains($route->uri(), 'interschool/api/seasons/{season}')
            && in_array('PUT', $route->methods());
    });

    expect($updateSeasonRoute)->not->toBeNull();
});

it('registers interschool event and sub-event update routes with put method', function () {
    $updateEventRoute = collect(Route::getRoutes())->first(function ($route) {
        return str_contains($route->uri(), 'interschool/api/events-settings/{id}')
            && in_array('PUT', $route->methods());
    });

    $updateSubEventRoute = collect(Route::getRoutes())->first(function ($route) {
        return str_contains($route->uri(), 'interschool/api/sub-events-settings/{id}')
            && in_array('PUT', $route->methods());
    });

    expect($updateEventRoute)->not->toBeNull();
    expect($updateSubEventRoute)->not->toBeNull();
});
