<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class StrictRoleMiddleware
{
    /**
     * Enforce the exact role (no super admin bypass), optionally scoped to a school.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     * @param  string|null  $schoolRequired
     */
    public function handle(Request $request, Closure $next, string $role, ?string $schoolRequired = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $schoolId = null;
        if ($schoolRequired) {
            $schoolParam = $request->route('school');
            if ($schoolParam) {
                if (is_object($schoolParam)) {
                    if (method_exists($schoolParam, 'getKey')) {
                        $schoolId = $schoolParam->getKey();
                    } elseif (property_exists($schoolParam, 'id')) {
                        $schoolId = $schoolParam->id;
                    }
                } else {
                    $schoolId = $schoolParam;
                }
            }
        }

        if (!$user->hasRole($role, $schoolId)) {
            abort(403, 'Insufficient permissions.');
        }

        if ($schoolId) {
            $request->attributes->set('current_school_id', $schoolId);
        }

        return $next($request);
    }
}
