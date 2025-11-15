<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            throw new AccessDeniedHttpException(__('আপনার এই কাজ করার অনুমতি নেই।'));
        }
        return $next($request);
    }
}
