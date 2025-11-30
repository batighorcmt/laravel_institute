<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\AdmissionApplication;
use App\Models\School;

class AdmissionController extends Controller
{
    /**
     * Handle applicant login using application ID (username) and password.
     * On success, store minimal applicant session and redirect to preview.
     */
    public function login(Request $request, string $schoolCode)
    {
        $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
        ]);

        $username = trim($request->input('username'));
        $password = $request->input('password');
        
        // Resolve school by code and constrain lookup to the same school
        $school = School::where('code', $schoolCode)->first();
        if (!$school) {
            Session::flash('admission_login_error', 'প্রতিষ্ঠান পাওয়া যায়নি');
            return back();
        }

        // Find application by app_id for this school
        $application = AdmissionApplication::query()
            ->where('school_id', $school->id)
            ->where('app_id', $username)
            ->first();

        if (!$application) {
            Session::flash('admission_login_error', 'ইউজারনেম বা পাসওয়ার্ড ভুল');
            return back();
        }

            // Verify password from multiple possible fields, safely handling non-bcrypt values
            $provided = $request->input('password');
            $data = is_array($application->data) ? $application->data : [];
            $possible = [
                $application->password ?? null,
                $application->applicant_password ?? null,
                $data['password_hashed'] ?? null,
                $data['applicant_password'] ?? null,
                $data['password'] ?? null,
            ];
            $matched = false;
            foreach ($possible as $stored) {
                if (!$stored || !is_string($stored)) { continue; }
                $isBcrypt = str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$2b$');
                $isArgon = str_starts_with($stored, '$argon2');
                if ($isBcrypt || $isArgon) {
                    if (Hash::check($provided, $stored)) { $matched = true; break; }
                } else {
                    if (hash_equals($stored, $provided)) { $matched = true; break; }
                }
            }

                if (!$matched) {
            Session::flash('admission_login_error', 'ইউজারনেম বা পাসওয়ার্ড ভুল');
            return back();
        }

        // Store applicant session
        Session::put('admission_applicant', [
            'app_id' => $application->app_id,
            'school_code' => $schoolCode,
            'name' => $application->name_bn ?? $application->name_en ?? null,
        ]);

        return redirect()->route('admission.preview', [$schoolCode, $application->app_id]);
    }
}
