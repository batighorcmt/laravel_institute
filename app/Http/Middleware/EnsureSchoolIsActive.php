<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureSchoolIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            /** @var \App\Models\User $user */

            // Super admins skip this check
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            // Determine if the current school context is inactive
            $schoolId = $request->route('school') ?? $request->route('school_id') ?? $request->input('school_id') ?? $request->input('school');
            
            if ($schoolId) {
                if (is_object($schoolId)) {
                    $school = $schoolId;
                } else {
                    $school = \App\Models\School::find($schoolId);
                }

                if ($school && $school->status !== 'active') {
                    return $this->forceLogout($request, "ডাটা লোড করতে ব্যর্থ");
                }
            } else {
                // If no school in request, check if the user has AT LEAST ONE active school role in an active school
                $hasActiveSchool = $user->activeSchoolRoles()
                    ->whereHas('school', function ($q) {
                        $q->where('status', 'active');
                    })
                    ->exists();

                if (!$hasActiveSchool) {
                    return $this->forceLogout($request, "ডাটা লোড করতে ব্যর্থ");
                }
            }
        }

        return $next($request);
    }

    protected function forceLogout(Request $request, $message)
    {
        Auth::logout();
        
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
