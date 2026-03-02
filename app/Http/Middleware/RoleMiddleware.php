<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     * @param  string|null  $schoolRequired
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // For super admin, allow access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if school context is required
        $schoolId = null;
        if (in_array('school', $roles)) {
            $schoolParam = $request->route('school') ?? $request->route('school_id');
            if ($schoolParam) {
                if (is_object($schoolParam)) {
                    $schoolId = method_exists($schoolParam, 'getKey') ? $schoolParam->getKey() : ($schoolParam->id ?? null);
                } else {
                    $schoolId = $schoolParam;
                }
            }
            // Filter out 'school' flag from roles
            $roles = array_filter($roles, fn($r) => $r !== 'school');
        }

        // Check for any of the roles
        $hasAccess = false;
        foreach ($roles as $role) {
            // Handle cases where comma-separated roles might be passed as a single string (e.g. role:parent,teacher)
            // though Laravel usually splits them if the middleware is defined with ...$roles
            if ($user->hasRole($role, $schoolId)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Insufficient permissions.');
        }

        // Store context
        if ($schoolId) {
            $request->attributes->set('current_school_id', $schoolId);
        }

        return $next($request);
    }
}
