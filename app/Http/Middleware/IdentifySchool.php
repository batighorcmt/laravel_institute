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
        $superAdminDomain = env('SUPER_ADMIN_DOMAIN', 'institute.batighorbd.com');

        // If it's the Super Admin domain, allow access to Super Admin dash.
        if ($domain === $superAdminDomain) {
            return $next($request);
        }

        // Try identifying school by domain
        try {
            $school = \App\Models\School::where('domain', $domain)->first();
        } catch (\Exception $e) {
            $school = null;
        }

        if (! $school) {
            // Pass through without setting school config so the application
            // can show the landing page or handle setup.
            return $next($request);
        }

        // Store globally
        session(['school_id' => $school->id]);
        config(['school.id' => $school->id]);

        return $next($request);
    }
}
