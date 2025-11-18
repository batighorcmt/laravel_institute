<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSchoolRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    public function index(School $school)
    {
        $teacherRoleId = Role::where('name', Role::TEACHER)->value('id');
        $teachers = UserSchoolRole::with(['user:id,first_name,last_name,name,phone'])
            ->where('school_id',$school->id)
            ->where('role_id',$teacherRoleId)
            ->orderByRaw('COALESCE(serial_number, 999999)')
            ->orderBy('id')
            ->get();
        $principalUserIds = UserSchoolRole::forSchool($school->id)
            ->withRole(Role::PRINCIPAL)
            ->pluck('user_id')
            ->all();
        return view('principal.teachers.index', compact('school','teachers','principalUserIds'));
    }

    public function store(Request $request, School $school)
    {
        $teacherRole = Role::where('name', Role::TEACHER)->firstOrFail();
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:32',
            'designation' => 'nullable|string|max:100',
            'serial_number' => 'nullable|integer|min:1',
        ]);
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => trim(($data['first_name']??'').' '.($data['last_name']??'')) ?: $data['first_name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => uniqid('t_').'@example.com', // placeholder
                'password' => bcrypt('password'), // default password, should force reset later
            ]);
            $pivot = UserSchoolRole::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'role_id' => $teacherRole->id,
                'status' => 'active',
                'designation' => $data['designation'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
            ]);
            DB::commit();
            return redirect()->back()->with('success','শিক্ষক যুক্ত হয়েছে');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error','ব্যর্থ: '.$e->getMessage());
        }
    }

    public function update(Request $request, School $school, UserSchoolRole $teacher)
    {
        // Allow principal to edit own info; others blocked unless super admin
        $current = Auth::user();
        $hasSuperAdmin = false;
        if ($current) {
            $hasSuperAdmin = \App\Models\UserSchoolRole::where('user_id',$current->id)
                ->where('status','active')
                ->whereHas('role', function($q){ $q->where('name', Role::SUPER_ADMIN); })
                ->exists();
        }
        if ($teacher->user && $teacher->user->isPrincipal($school->id)) {
            if (!$current) {
                return redirect()->back()->with('error','অননুমোদিত অ্যাকশন');
            }
            if (!$hasSuperAdmin && $current->id !== $teacher->user_id) {
                return redirect()->back()->with('error','প্রধান শিক্ষকের তথ্য কেবল নিজে বা সুপার অ্যাডমিন সম্পাদনা করতে পারবেন');
            }
        }
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:32',
            'designation' => 'nullable|string|max:100',
            'serial_number' => 'nullable|integer|min:1',
        ]);
        DB::beginTransaction();
        try {
            $teacher->user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'name' => trim(($data['first_name']??'').' '.($data['last_name']??'')) ?: $data['first_name'],
                'phone' => $data['phone'] ?? null,
            ]);
            $teacher->update([
                'designation' => $data['designation'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
            ]);
            DB::commit();
            return redirect()->back()->with('success','আপডেট সফল');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error','আপডেট ব্যর্থ: '.$e->getMessage());
        }
    }

    public function destroy(School $school, UserSchoolRole $teacher)
    {
        try {
            $current = Auth::user();
            $hasSuperAdmin = false;
            if ($current) {
                $hasSuperAdmin = \App\Models\UserSchoolRole::where('user_id',$current->id)
                    ->where('status','active')
                    ->whereHas('role', function($q){ $q->where('name', Role::SUPER_ADMIN); })
                    ->exists();
            }
            if ($teacher->user && $teacher->user->isPrincipal($school->id) && (!$current || !$hasSuperAdmin)) {
                return redirect()->back()->with('error','প্রধান শিক্ষকের তথ্য কেবল সুপার অ্যাডমিন মুছতে পারবেন');
            }
            $teacher->delete();
            return redirect()->back()->with('success','শিক্ষক মুছে ফেলা হয়েছে');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error','মুছতে ব্যর্থ: '.$e->getMessage());
        }
    }
}
