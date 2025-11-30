<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdmissionApplicantExclusive
{
    /**
     * Prevent starting a new application while an applicant session exists.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = session('admission_applicant');
        if ($session) {
            $schoolCode = $request->route('schoolCode');
            return response()->view('admission.blocked', [
                'schoolCode' => $schoolCode,
                'title' => 'বর্তমান আবেদনকারী লগইন রয়েছে',
                'message' => 'বর্তমান আবেদনকারী লগইন থাকা অবস্থায় নতুন আবেদন করা যাবে না। আগে লগআউট করুন।',
                'showLogout' => true,
            ], 403);
        }
        return $next($request);
    }
}
