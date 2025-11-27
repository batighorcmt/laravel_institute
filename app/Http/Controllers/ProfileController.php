<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $roles = $user->activeSchoolRoles()->with(['role','school'])->get();
        return view('profile.index', compact('user','roles'));
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->password_changed_at = now();
        $user->save();

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateAvatar(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'avatar' => ['required','image','max:2048'],
        ]);

        $path = $request->file('avatar')->store('avatars','public');
        // Optionally delete old avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            try { Storage::disk('public')->delete($user->avatar); } catch (\Throwable $e) {}
        }
        $user->avatar = $path;
        $user->save();

        return back()->with('success','Profile photo updated.');
    }
}
