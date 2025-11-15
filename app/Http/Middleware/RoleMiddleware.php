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
    public function handle(Request $request, Closure $next, string $role, ?string $schoolRequired = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // For super admin, allow access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required role
        $schoolId = null;
        if ($schoolRequired && $request->route('school')) {
            $schoolId = $request->route('school');
        }

        if (!$user->hasRole($role, $schoolId)) {
            abort(403, 'Insufficient permissions.');
        }

        // Store current school context for the request
        if ($schoolId) {
            $request->attributes->set('current_school_id', $schoolId);
        }

        return $next($request);
    }
}
