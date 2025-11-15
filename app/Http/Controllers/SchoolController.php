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
            'code' => ['required','string','max:50','unique:schools,code'],
            'address' => ['nullable','string','max:500'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'website' => ['nullable','url','max:255'],
            'description' => ['nullable','string'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'logo' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
        ]);

        $defaultAdminInfo = null;
        DB::transaction(function () use (&$data, $request, &$defaultAdminInfo) {
            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('logos','public');
            }
            $school = School::create($data);

            // Create default admin user for this school (Principal role)
            $role = Role::where('name', Role::PRINCIPAL)->first();
            if ($role) {
                // Generate unique email based on code
                $baseEmail = strtolower(preg_replace('/[^a-z0-9]+/i', '', $school->code ?? Str::slug($school->name)));
                $email = $baseEmail ? ("admin_{$baseEmail}@example.com") : ('admin'.Str::random(4).'@example.com');
                // Ensure email unique
                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = "admin_{$baseEmail}{$counter}@example.com";
                    $counter++;
                }
                $passwordPlain = Str::password(10);
                $user = User::create([
                    'name' => $school->name.' Admin',
                    'email' => $email,
                    'password' => Hash::make($passwordPlain),
                    'status' => 'active',
                ]);
                UserSchoolRole::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'role_id' => $role->id,
                    'status' => 'active',
                ]);

                $defaultAdminInfo = [
                    'email' => $email,
                    'password' => $passwordPlain,
                ];
            }
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
        return view('superadmin.schools.show', compact('school'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(School $school)
    {
        return view('superadmin.schools.edit', compact('school'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, School $school)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'code' => ['required','string','max:50', Rule::unique('schools','code')->ignore($school->id)],
            'address' => ['nullable','string','max:500'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'website' => ['nullable','url','max:255'],
            'description' => ['nullable','string'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'logo' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
        ]);

        DB::transaction(function () use (&$data, $request, $school) {
            if ($request->hasFile('logo')) {
                if ($school->logo) {
                    Storage::disk('public')->delete($school->logo);
                }
                $data['logo'] = $request->file('logo')->store('logos','public');
            }
            $school->update($data);
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
}
