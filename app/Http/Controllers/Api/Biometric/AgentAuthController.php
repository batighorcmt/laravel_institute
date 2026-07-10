<?php

namespace App\Http\Controllers\Api\Biometric;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\School;

class AgentAuthController extends Controller
{
    /**
     * Authenticate the C# local agent.
     */
    public function login(Request $request)
    {
        $request->validate([
            'school_code' => 'required|string',
            'agent_token' => 'required|string',
        ]);

        $school = School::where('code', $request->school_code)->first();

        if (!$school) {
            return response()->json(['message' => 'School not found'], 404);
        }

        if (!$school->agent_token || $school->agent_token !== $request->agent_token) {
            return response()->json(['message' => 'Invalid agent token'], 401);
        }

        $wasOnline = $school->agent_last_seen && Carbon::parse($school->agent_last_seen)->diffInMinutes(now()) <= 5;

        $school->update([
            'agent_last_seen' => now(),
            'agent_online_since' => $wasOnline ? ($school->agent_online_since ?? now()) : now(),
        ]);

        return response()->json([
            'message' => 'Authenticated successfully',
            'school_id' => $school->id,
            'token' => $school->agent_token // Using agent_token as the bearer token for simplicity
        ]);
    }

    /**
     * Periodic heartbeat from the C# local agent to indicate it's online.
     */
    public function heartbeat(Request $request)
    {
        $token = $request->bearerToken() ?? $request->agent_token;
        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $school = School::where('agent_token', $token)->first();
        if (!$school) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $wasOnline = $school->agent_last_seen && Carbon::parse($school->agent_last_seen)->diffInMinutes(now()) <= 5;

        $school->update([
            'agent_last_seen' => now(),
            'agent_online_since' => $wasOnline ? ($school->agent_online_since ?? now()) : now(),
        ]);

        return response()->json(['message' => 'Agent heartbeat logged']);
    }
}
