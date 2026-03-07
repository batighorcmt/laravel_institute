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

        // Check for active school
        if (!$user->isSuperAdmin() && !$user->activeSchoolRoles()->whereHas('school', fn($s) => $s->where('status', 'active'))->exists()) {
            return response()->json([
                'message' => 'আপনার প্রতিষ্ঠানটি বর্তমানে ইনএকটিভ রয়েছে।'
            ], 403);
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
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $this->extractRoles($user),
            'bn_name' => $user->name, // default
            'mobile' => $user->username, // default for mobile login users
            'photo_url' => $user->avatar ? \Illuminate\Support\Facades\Storage::url($user->avatar) : null,
        ];

        // Parent specific details
        $isParent = collect($payload['roles'])->contains('role', 'parent');
        if ($isParent) {
            $student = \App\Models\Student::where('guardian_phone', $user->username)
                ->orWhere('guardian_phone', $user->email)
                ->first();
            if ($student) {
                $payload['bn_name'] = $student->guardian_name_bn ?: ($student->guardian_name_en ?: $student->student_name_bn);
                $payload['name'] = $student->guardian_name_en ?: ($student->guardian_name_bn ?: $student->student_name_en);
                $payload['mobile'] = $student->guardian_phone;
            }
        }

        // If the user is a teacher for a school, include teacher designation and photo
        $schoolId = $user->firstTeacherSchoolId();
        if ($schoolId) {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->first();
            if ($teacher) {
                $photoUrl = null;
                if ($teacher->photo) {
                    if (str_starts_with($teacher->photo, 'http')) {
                        $photoUrl = $teacher->photo;
                    } else {
                        $storageUrl = \Illuminate\Support\Facades\Storage::url($teacher->photo);
                        $photoUrl = rtrim(config('app.url'), '/') . '/' . ltrim($storageUrl, '/');
                    }
                }
                $payload['teacher'] = [
                    'id' => $teacher->id,
                    'designation' => $teacher->designation,
                    'phone' => $teacher->phone,
                    'photo_url' => $photoUrl,
                ];
            }
        }

        $payload['username'] = $user->username;

        return response()->json($payload);
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

    /**
     * Change user password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The provided current password is incorrect.'
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($validated['new_password']),
        ])->save();

        return response()->json(['message' => 'Password updated successfully.']);
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

