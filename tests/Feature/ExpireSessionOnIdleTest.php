<?php

use App\Http\Middleware\ExpireSessionOnIdle;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;

function makeRequestWithSession(array $sessionData): Request
{
    $store = new Store('test-session', new ArraySessionHandler(120));
    $store->start();
    foreach ($sessionData as $key => $value) {
        $store->put($key, $value);
    }

    $request = Request::create('/principal/dashboard', 'GET');
    $request->setLaravelSession($store);

    return $request;
}

it('lets a fresh session through and stamps last-activity', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('logout')->never();

    $request = makeRequestWithSession(['_last_activity_at' => time() - 10]);

    $response = (new ExpireSessionOnIdle())->handle($request, fn ($req) => response('ok'));

    expect($response->getContent())->toBe('ok');
    expect($request->session()->get('_last_activity_at'))->toBeGreaterThan(time() - 2);
});

it('force-logs-out once the session has been idle past the configured lifetime', function () {
    config(['session.lifetime' => 60]); // minutes

    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('logout')->once();

    // 61 minutes ago — one minute past the 60-minute lifetime.
    $request = makeRequestWithSession(['_last_activity_at' => time() - (61 * 60)]);

    $response = (new ExpireSessionOnIdle())->handle($request, fn ($req) => response('should not reach here'));

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('login');
});

it('does not touch guests', function () {
    Auth::shouldReceive('check')->andReturn(false);
    Auth::shouldReceive('logout')->never();

    $request = makeRequestWithSession([]);

    $response = (new ExpireSessionOnIdle())->handle($request, fn ($req) => response('ok'));

    expect($response->getContent())->toBe('ok');
});
