<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Allow username or email in the same field
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $login = $request->input('email');
        $password = $request->input('password');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $login, 'password' => $password], $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            /** @var User $user */
            
            // Check if user has any active roles
            if (!$user->activeSchoolRoles()->exists()) {
                Auth::logout();
                return redirect()->back()->withErrors([
                    'email' => 'Your account is not assigned to any school or role.',
                ]);
            }

            // Redirect based on user's role
            return $this->redirectToDashboard($user);
        }

        return redirect()->back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    protected function redirectToDashboard($user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->isPrincipal()) {
            return redirect()->route('principal.dashboard');
        } elseif ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        } elseif ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
