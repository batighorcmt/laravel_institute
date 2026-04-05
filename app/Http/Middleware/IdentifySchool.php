<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifySchool
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domain = preg_replace('/^www\./', '', $request->getHost());
        $superAdminDomain = 'institute.batighorbd.com';

        // If it's the Super Admin domain, allow access to Super Admin dash.
        if ($domain === $superAdminDomain) {
            return $next($request);
        }

        // Try identifying school by domain
        $school = \App\Models\School::where('domain', $domain)->first();

        if (!$school) {
            // Also allow local testing or direct access if necessary
            // For now, abort as requested for school domains
            if (app()->environment('local') && ($domain === 'localhost' || $domain === '127.0.0.1')) {
                return $next($request);
            }
            abort(404, 'School Not Found');
        }

        // Store globally
        session(['school_id' => $school->id]);
        config(['school.id' => $school->id]);

        return $next($request);
    }
}
