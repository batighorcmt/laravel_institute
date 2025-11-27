<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Issue a Sanctum personal access token for mobile clients.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
            'device_name' => ['required','string']
        ]);

        $user = User::where('username', $validated['username'])
            ->orWhere('email', $validated['username'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 422);
        }

        // Create token (consider scoping later)
        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'roles' => $this->extractRoles($user),
            ]
        ]);
    }

    /**
     * Return authenticated user profile & roles.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $this->extractRoles($user),
        ]);
    }

    /**
     * Revoke current token (logout).
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->json(['message' => 'Logged out']);
    }

    private function extractRoles(User $user): array
    {
        // Collect active role names with school context (if available)
        $roles = [];
        foreach ($user->activeSchoolRoles()->with(['role','school'])->get() as $pivot) {
            $roles[] = [
                'role' => $pivot->role->name,
                'school_id' => $pivot->school_id,
                'school_name' => $pivot->school?->name,
            ];
        }
        return $roles;
    }
}
