<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSchoolRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = School::query()->orderByDesc('id');
        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('name','like',"%$search%")
                  ->orWhere('code','like',"%$search%")
                  ->orWhere('phone','like',"%$search%")
                  ->orWhere('email','like',"%$search%")
                ;
            });
        }
        $allowed = [10, 25, 50, 100];
        $perPage = (int) $request->get('per_page', 10);
        if (!in_array($perPage, $allowed, true)) { $perPage = 10; }
        $schools = $query->paginate($perPage)->withQueryString();
        return view('superadmin.schools.index', compact('schools', 'perPage', 'allowed'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('superadmin.schools.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'name_bn' => ['nullable','string','max:255'],
            'code' => ['required','string','max:50','unique:schools,code'],
            'address' => ['nullable','string','max:500'],
            'address_bn' => ['nullable','string','max:500'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'website' => ['nullable','url','max:255'],
            'description' => ['nullable','string'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'logo' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
            // Principal block
            'principal_name_en' => ['required','string','max:255'],
            'principal_name_bn' => ['required','string','max:255'],
            'principal_designation' => ['required','string','max:100'],
            'principal_phone' => ['required','string','max:32'],
            'principal_email' => ['required','email','max:255','unique:users,email'],
        ]);

        $defaultAdminInfo = null;
        DB::transaction(function () use (&$data, $request, &$defaultAdminInfo) {
            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('logos','public');
            }
            $school = School::create($data);

            // Create Principal user using provided info
            $principalRole = Role::where('name', Role::PRINCIPAL)->first();
            $teacherRole = Role::where('name', Role::TEACHER)->first();
            $passwordPlain = Str::password(10);
            $user = User::create([
                'name' => $data['principal_name_en'],
                'first_name' => $data['principal_name_en'],
                'email' => $data['principal_email'],
                'phone' => $data['principal_phone'],
                'password' => Hash::make($passwordPlain),
                'status' => 'active',
            ]);
            if ($principalRole) {
                UserSchoolRole::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'role_id' => $principalRole->id,
                    'status' => 'active',
                    'designation' => $data['principal_designation'] ?? null,
                ]);
            }
            if ($teacherRole) {
                UserSchoolRole::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'role_id' => $teacherRole->id,
                    'status' => 'active',
                    'designation' => $data['principal_designation'] ?? null,
                    'serial_number' => 1,
                ]);
            }

            $defaultAdminInfo = [
                'email' => $data['principal_email'],
                'password' => $passwordPlain,
            ];
        });

        $flash = ['success' => __('স্কুল সফলভাবে যুক্ত হয়েছে।')];
        if ($defaultAdminInfo) {
            $flash['default_admin'] = $defaultAdminInfo;
        }
        return redirect()->route('superadmin.schools.index')->with($flash);
    }

    /**
     * Display the specified resource.
     */
    public function show(School $school)
    {
        $principal = UserSchoolRole::forSchool($school->id)
            ->withRole(Role::PRINCIPAL)
            ->with('user')
            ->first();
        return view('superadmin.schools.show', compact('school','principal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(School $school)
    {
        $principal = UserSchoolRole::forSchool($school->id)
            ->withRole(Role::PRINCIPAL)
            ->with('user')
            ->first();
        return view('superadmin.schools.edit', compact('school','principal'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, School $school)
    {
        $principalPivot = UserSchoolRole::forSchool($school->id)->withRole(Role::PRINCIPAL)->first();
        $principalUserId = $principalPivot?->user_id;
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'name_bn' => ['nullable','string','max:255'],
            'code' => ['required','string','max:50', Rule::unique('schools','code')->ignore($school->id)],
            'address' => ['nullable','string','max:500'],
            'address_bn' => ['nullable','string','max:500'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'website' => ['nullable','url','max:255'],
            'description' => ['nullable','string'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'logo' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
            // Principal block (optional on edit but if provided, validate)
            'principal_name_en' => ['nullable','string','max:255'],
            'principal_name_bn' => ['nullable','string','max:255'],
            'principal_designation' => ['nullable','string','max:100'],
            'principal_phone' => ['nullable','string','max:32'],
            'principal_email' => ['nullable','email','max:255', Rule::unique('users','email')->ignore($principalUserId)],
        ]);

        DB::transaction(function () use (&$data, $request, $school, $principalPivot) {
            if ($request->hasFile('logo')) {
                if ($school->logo) {
                    Storage::disk('public')->delete($school->logo);
                }
                $data['logo'] = $request->file('logo')->store('logos','public');
            }
            $school->update($data);

            // If principal info provided, update or create principal user
            if (!empty($data['principal_email']) || !empty($data['principal_name_en']) || !empty($data['principal_phone'])) {
                $principalRole = Role::where('name', Role::PRINCIPAL)->first();
                $teacherRole = Role::where('name', Role::TEACHER)->first();
                if ($principalPivot) {
                    // Update existing principal user
                    $principalUser = User::find($principalPivot->user_id);
                    if ($principalUser) {
                        $principalUser->update([
                            'name' => $data['principal_name_en'] ?: $principalUser->name,
                            'first_name' => $data['principal_name_en'] ?: $principalUser->first_name,
                            'phone' => $data['principal_phone'] ?: $principalUser->phone,
                            'email' => $data['principal_email'] ?: $principalUser->email,
                            'status' => 'active',
                        ]);
                    }
                    $principalPivot->update([
                        'designation' => $data['principal_designation'] ?? $principalPivot->designation,
                        'status' => 'active',
                    ]);
                    // Ensure teacher pivot exists
                    if ($teacherRole && !UserSchoolRole::where('user_id',$principalPivot->user_id)->where('school_id',$school->id)->where('role_id',$teacherRole->id)->exists()) {
                        UserSchoolRole::create([
                            'user_id' => $principalPivot->user_id,
                            'school_id' => $school->id,
                            'role_id' => $teacherRole->id,
                            'status' => 'active',
                            'designation' => $data['principal_designation'] ?? null,
                            'serial_number' => 1,
                        ]);
                    }
                } else {
                    // Create new principal user if missing
                    if (!empty($data['principal_email'])) {
                        $passwordPlain = Str::password(10);
                        $user = User::create([
                            'name' => $data['principal_name_en'] ?: ($school->name.' Admin'),
                            'first_name' => $data['principal_name_en'] ?: null,
                            'email' => $data['principal_email'],
                            'phone' => $data['principal_phone'] ?? null,
                            'password' => Hash::make($passwordPlain),
                            'status' => 'active',
                        ]);
                        if ($principalRole) {
                            UserSchoolRole::create([
                                'user_id' => $user->id,
                                'school_id' => $school->id,
                                'role_id' => $principalRole->id,
                                'status' => 'active',
                                'designation' => $data['principal_designation'] ?? null,
                            ]);
                        }
                        if ($teacherRole) {
                            UserSchoolRole::create([
                                'user_id' => $user->id,
                                'school_id' => $school->id,
                                'role_id' => $teacherRole->id,
                                'status' => 'active',
                                'designation' => $data['principal_designation'] ?? null,
                                'serial_number' => 1,
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()->route('superadmin.schools.index')
            ->with('success', __('স্কুল তথ্য আপডেট হয়েছে।'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(School $school)
    {
        DB::transaction(function () use ($school) {
            if ($school->logo) {
                Storage::disk('public')->delete($school->logo);
            }
            $school->delete();
        });
        return redirect()->route('superadmin.schools.index')
            ->with('success', __('স্কুল মুছে ফেলা হয়েছে।'));
    }

    /**
     * Show a school management landing page.
     */
    public function manage(School $school)
    {
        return view('superadmin.schools.manage', compact('school'));
    }

    /**
     * Reset the school's principal password and show the new password once.
     */
    public function resetPassword(School $school)
    {
        $principalPivot = UserSchoolRole::forSchool($school->id)->withRole(Role::PRINCIPAL)->first();
        if (!$principalPivot) {
            return redirect()->route('superadmin.schools.index')
                ->with('error', __('প্রধান শিক্ষক/অধ্যক্ষ ইউজার পাওয়া যায়নি।'));
        }
        $user = User::find($principalPivot->user_id);
        if (!$user) {
            return redirect()->route('superadmin.schools.index')
                ->with('error', __('ইউজার পাওয়া যায়নি।'));
        }

        $newPassword = Str::password(10);
        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($newPassword)]);

        return redirect()->route('superadmin.schools.index')
            ->with('success', __('পাসওয়ার্ড রিসেট করা হয়েছে।'))
            ->with('default_admin', [
                'email' => $user->email,
                'password' => $newPassword,
                'school_id' => $school->id,
                'school_name' => $school->name,
            ]);
    }
}
