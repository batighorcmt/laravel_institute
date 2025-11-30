<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdmissionApplicantGuard
{
    /**
     * Ensure applicant session exists and matches current schoolCode route param.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = session('admission_applicant');
        $schoolCode = (string) $request->route('schoolCode');

        if (!$session || ($session['school_code'] ?? null) !== $schoolCode) {
            return response()->view('admission.blocked', [
                'schoolCode' => $schoolCode,
                'title' => 'দেখার অনুমতি নেই',
                'message' => 'আবেদনকারী লগইন নেই বা প্রতিষ্ঠান মেলেনি।',
                'showLogout' => true,
            ], 403);
        }

        return $next($request);
    }
}
