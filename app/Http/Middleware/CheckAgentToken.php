<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\School;

class CheckAgentToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?? $request->agent_token;
        if (!$token) {
            return response()->json(['message' => 'Unauthorized - No agent token provided'], 401);
        }

        $school = School::where('agent_token', $token)->first();
        if (!$school) {
            return response()->json(['message' => 'Unauthorized - Invalid agent token'], 401);
        }

        // Make sure the request uses the authenticated school's ID to prevent cross-school data injection
        $request->merge(['auth_school_id' => $school->id]);

        return $next($request);
    }
}
