<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\School;

class SchoolContextMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super admin can select any school or work without school context
        if ($user->isSuperAdmin()) {
            $currentSchoolId = Session::get('current_school_id');
            if ($currentSchoolId) {
                $school = School::find($currentSchoolId);
                if ($school) {
                    $request->attributes->set('current_school', $school);
                    $request->attributes->set('current_school_id', $currentSchoolId);
                }
            }
            return $next($request);
        }

        // For other users, get their schools and set context
        $userSchools = $user->getSchoolsForRole($user->activeSchoolRoles->first()?->role?->name);
        
        if ($userSchools->isEmpty()) {
            abort(403, 'No school access assigned.');
        }

        // If user has only one school, auto-select it
        if ($userSchools->count() === 1) {
            $school = $userSchools->first();
            Session::put('current_school_id', $school->id);
            $request->attributes->set('current_school', $school);
            $request->attributes->set('current_school_id', $school->id);
        } else {
            // If user has multiple schools, check session for current selection
            $currentSchoolId = Session::get('current_school_id');
            if ($currentSchoolId && $userSchools->contains('id', $currentSchoolId)) {
                $school = $userSchools->where('id', $currentSchoolId)->first();
                $request->attributes->set('current_school', $school);
                $request->attributes->set('current_school_id', $currentSchoolId);
            } else {
                // Redirect to school selection page
                return redirect()->route('select.school');
            }
        }

        return $next($request);
    }
}
