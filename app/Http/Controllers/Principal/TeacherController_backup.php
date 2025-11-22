<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Role;
use App\Models\User;
use App\Models\Teacher;
use App\Models\UserSchoolRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function index(School $school)
    {
        $teachers = Teacher::with(['user:id,username,email'])
            ->where('school_id', $school->id)
            ->orderByRaw('COALESCE(serial_number, 999999)')
            ->orderBy('id')
            ->get();
        
        $principalUserIds = UserSchoolRole::forSchool($school->id)
            ->withRole(Role::PRINCIPAL)
            ->pluck('user_id')
            ->all();
        
        return view('principal.teachers.index', compact('school','teachers','principalUserIds'));
    }

    public function create(School $school)
    {
        // empty teacherRole for the form partial
        return view('principal.teachers.create', compact('school'));
    }

    public function edit(School $school, Teacher $teacher)
    {
        // authorize that this is a teacher for this school
        if ($teacher->school_id !== $school->id) abort(404);
        return view('principal.teachers.edit', ['school'=>$school, 'teacher'=>$teacher]);
    }

    public function store(Request $request, School $school)
    {
        $teacherRole = Role::where('name', Role::TEACHER)->firstOrFail();
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'first_name_bn' => 'nullable|string|max:191',
            'last_name_bn' => 'nullable|string|max:191',
            'father_name_bn' => 'nullable|string|max:191',
            'father_name_en' => 'nullable|string|max:191',
            'mother_name_bn' => 'nullable|string|max:191',
            'mother_name_en' => 'nullable|string|max:191',
            'date_of_birth' => 'nullable|date',
            'joining_date' => 'nullable|date',
            'academic_info' => 'nullable|string',
            'qualification' => 'nullable|string',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:191',
            'designation' => 'nullable|string|max:100',
            'serial_number' => 'nullable|integer|min:1',
            'photo' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:2048',
        ]);
        DB::beginTransaction();
        try {
            // Generate username: <school_code>T<3digit_serial>
            $serialNumber = $data['serial_number'] ?? 1;
            $username = $school->code . 'T' . str_pad($serialNumber, 3, '0', STR_PAD_LEFT);
            
            // Generate 6-digit random password
            $plainPassword = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $email = $data['email'] ?? (uniqid('t_').'@example.com');
            $userData = [
                'name' => trim(($data['first_name']??'').' '.($data['last_name']??'')) ?: $data['first_name'],
                'username' => $username,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'first_name_bn' => $data['first_name_bn'] ?? null,
                'last_name_bn' => $data['last_name_bn'] ?? null,
                'father_name_bn' => $data['father_name_bn'] ?? null,
                'father_name_en' => $data['father_name_en'] ?? null,
                'mother_name_bn' => $data['mother_name_bn'] ?? null,
                'mother_name_en' => $data['mother_name_en'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'joining_date' => $data['joining_date'] ?? null,
                'academic_info' => $data['academic_info'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $email,
                'password' => bcrypt($plainPassword),
                'plain_password' => $plainPassword,
            ];

            $user = User::create($userData);
            // handle uploads
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('teachers/photos','public');
                $user->photo = $path; $user->save();
            }
            if ($request->hasFile('signature')) {
                $path = $request->file('signature')->store('teachers/signatures','public');
                $user->signature = $path; $user->save();
            }
            $pivot = UserSchoolRole::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'role_id' => $teacherRole->id,
                'status' => 'active',
                'designation' => $data['designation'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
            ]);
            DB::commit();
            return redirect()->back()->with('success','শিক্ষক যুক্ত হয়েছে');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error','ব্যর্থ: '.$e->getMessage());
        }
    }

    public function update(Request $request, School $school, Teacher $teacher)
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
            'first_name_bn' => 'nullable|string|max:191',
            'last_name_bn' => 'nullable|string|max:191',
            'father_name_bn' => 'nullable|string|max:191',
            'father_name_en' => 'nullable|string|max:191',
            'mother_name_bn' => 'nullable|string|max:191',
            'mother_name_en' => 'nullable|string|max:191',
            'date_of_birth' => 'nullable|date',
            'joining_date' => 'nullable|date',
            'academic_info' => 'nullable|string',
            'qualification' => 'nullable|string',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:191',
            'designation' => 'nullable|string|max:100',
            'serial_number' => 'nullable|integer|min:1',
            'photo' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:2048',
        ]);
        DB::beginTransaction();
        try {
            $userUpdates = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'first_name_bn' => $data['first_name_bn'] ?? null,
                'last_name_bn' => $data['last_name_bn'] ?? null,
                'father_name_bn' => $data['father_name_bn'] ?? null,
                'father_name_en' => $data['father_name_en'] ?? null,
                'mother_name_bn' => $data['mother_name_bn'] ?? null,
                'mother_name_en' => $data['mother_name_en'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'joining_date' => $data['joining_date'] ?? null,
                'academic_info' => $data['academic_info'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? $teacher->user->email,
                'name' => trim(($data['first_name']??'').' '.($data['last_name']??'')) ?: $data['first_name'],
            ];
            $teacher->user->update($userUpdates);
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('teachers/photos','public');
                $teacher->user->photo = $path; $teacher->user->save();
            }
            if ($request->hasFile('signature')) {
                $path = $request->file('signature')->store('teachers/signatures','public');
                $teacher->user->signature = $path; $teacher->user->save();
            }
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
