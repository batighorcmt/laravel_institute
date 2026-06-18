<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\UserSchoolRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Impersonate the principal of the given school.
     */
    public function impersonate(School $school)
    {
        // Must be superadmin to initiate
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Only SuperAdmins can impersonate.');
        }

        // Get Principal Role ID
        $principalRoleId = \Illuminate\Support\Facades\DB::table('roles')->where('name', 'principal')->value('id');

        // Find the principal user of the school
        $principalRole = UserSchoolRole::where('school_id', $school->id)
            ->where('role_id', $principalRoleId)
            ->first();

        if (!$principalRole) {
            return back()->with('error', 'এই স্কুলের কোনো প্রতিষ্ঠান প্রধান (Principal) পাওয়া যায়নি।');
        }

        $principalUser = User::find($principalRole->user_id);

        if (!$principalUser) {
            return back()->with('error', 'প্রতিষ্ঠান প্রধানের ইউজার একাউন্টটি পাওয়া যায়নি।');
        }

        // Store current superadmin id in session
        session()->put('impersonated_by', auth()->id());
        session()->put('impersonated_school_name', $school->name);

        // Login as the principal
        Auth::login($principalUser);

        // Redirect to principal dashboard
        return redirect()->route('principal.dashboard')->with('success', 'আপনি এখন '.$school->name.' এর প্রতিষ্ঠান প্রধান হিসেবে লগইন করেছেন।');
    }

    /**
     * Leave impersonation and revert back to superadmin.
     */
    public function leave()
    {
        if (session()->has('impersonated_by')) {
            $superAdminId = session()->pull('impersonated_by');
            session()->forget('impersonated_school_name');
            
            $superAdminUser = User::find($superAdminId);
            
            if ($superAdminUser && $superAdminUser->isSuperAdmin()) {
                Auth::login($superAdminUser);
                return redirect()->route('superadmin.dashboard')->with('success', 'আপনি সুপার এডমিন ড্যাশবোর্ডে ফিরে এসেছেন।');
            }
        }

        // Failsafe if session is corrupted or user not found
        Auth::logout();
        return redirect()->route('login');
    }
}
