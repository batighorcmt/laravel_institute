<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        // Try to get school ID from route parameter first, then session
        $routeSchool = $request->route('school');
        $schoolId = is_object($routeSchool) ? $routeSchool->id : $routeSchool;
        
        if (!$schoolId) {
            $schoolId = session('active_school_id');
        }

        if (!$schoolId && auth()->check()) {
            // Fallback to user's primary school if not in session
            $schoolId = auth()->user()->primarySchool()?->id;
        }

        if (!$schoolId) {
            return $next($request);
        }

        $isEnabled = \App\Models\School::find($schoolId)
            ->modules()
            ->where('slug', $moduleSlug)
            ->where('is_enabled', true)
            ->exists();

        if (!$isEnabled) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('এই মডিউল ব্যবহারের অনুমতি নেই।')], 403);
            }
            return abort(403, __('এই মডিউল ব্যবহারের অনুমতি নেই।'));
        }

        return $next($request);
    }
}
