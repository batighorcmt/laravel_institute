<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\School;

class PrincipalController extends Controller
{
    /**
     * Show principal dashboard.
     */
    public function dashboard(Request $request)
    {
        // Later: aggregate stats for classes, subjects, teachers, students under principal's schools.
        return view('principal.dashboard');
    }

    /**
     * Institute menu landing for principals.
     * If user has single school, redirect to manage; else show chooser.
     */
    public function institute()
    {
    $user = Auth::user(); /** @var User $user */
        $schools = $user->getSchoolsForRole(\App\Models\Role::PRINCIPAL);
        if ($schools->count() === 1) {
            return redirect()->route('principal.institute.manage', $schools->first());
        }
        return view('principal.institute.index', compact('schools'));
    }

    /**
     * Manage settings for a specific school (principal scope).
     */
    public function manageSchool(\App\Models\School $school)
    {
        // Authorization: ensure current user is principal for this school
    $current = Auth::user(); /** @var User $current */
    abort_unless($current->isPrincipal($school->id) || $current->isSuperAdmin(), 403);
        return view('principal.institute.manage', compact('school'));
    }
}
