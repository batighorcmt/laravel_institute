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
            // Find next available username
            $schoolCode = $school->code;
            $counter = 1;
            
            // Find the highest existing username number for this school
            $existingUsernames = User::where('username', 'LIKE', $schoolCode . 'T%')
                ->whereNotNull('username')
                ->pluck('username')
                ->map(function($username) use ($schoolCode) {
                    $num = str_replace($schoolCode . 'T', '', $username);
                    return is_numeric($num) ? (int)$num : 0;
                })
                ->filter()
                ->toArray();
            
            if (!empty($existingUsernames)) {
                $counter = max($existingUsernames) + 1;
            }
            
            // Generate unique username
            $username = $schoolCode . 'T' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            
            // Double check uniqueness (safety)
            while (User::where('username', $username)->exists()) {
                $counter++;
                $username = $schoolCode . 'T' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            }
            
            // Generate 6-digit random password
            $plainPassword = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $email = $data['email'] ?? (uniqid('t_').'@example.com');
            
            // Step 1: Create User (authentication only)
            $user = User::create([
                'name' => trim(($data['first_name']??'').' '.($data['last_name']??'')) ?: $data['first_name'],
                'username' => $username,
                'email' => $email,
                'password' => bcrypt($plainPassword),
            ]);
            
            // Step 2: Create UserSchoolRole (role assignment)
            UserSchoolRole::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'role_id' => $teacherRole->id,
                'status' => 'active',
            ]);
            
            // Step 3: Handle file uploads
            $photoPath = null;
            $signaturePath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('teachers/photos','public');
            }
            if ($request->hasFile('signature')) {
                $signaturePath = $request->file('signature')->store('teachers/signatures','public');
            }
            
            // Step 4: Create Teacher (all profile data)
            Teacher::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'first_name_bn' => $data['first_name_bn'] ?? null,
                'last_name_bn' => $data['last_name_bn'] ?? null,
                'father_name_bn' => $data['father_name_bn'] ?? null,
                'father_name_en' => $data['father_name_en'] ?? null,
                'mother_name_bn' => $data['mother_name_bn'] ?? null,
                'mother_name_en' => $data['mother_name_en'] ?? null,
                'phone' => $data['phone'] ?? null,
                'plain_password' => $plainPassword,
                'designation' => $data['designation'] ?? null,
                'serial_number' => $data['serial_number'] ?? $counter,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'joining_date' => $data['joining_date'] ?? null,
                'academic_info' => $data['academic_info'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'photo' => $photoPath,
                'signature' => $signaturePath,
                'status' => 'active',
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
            // Update teacher profile
            $teacherUpdates = [
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
                'designation' => $data['designation'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
            ];
            
            // Handle photo upload
            if ($request->hasFile('photo')) {
                $teacherUpdates['photo'] = $request->file('photo')->store('teachers/photos','public');
            }
            
            // Handle signature upload
            if ($request->hasFile('signature')) {
                $teacherUpdates['signature'] = $request->file('signature')->store('teachers/signatures','public');
            }
            
            $teacher->update($teacherUpdates);
            
            // Update user email and name
            $teacher->user->update([
                'email' => $data['email'] ?? $teacher->user->email,
                'name' => trim(($data['first_name']??'').' '.($data['last_name']??'')) ?: $data['first_name'],
            ]);
            DB::commit();
            return redirect()->back()->with('success','আপডেট সফল');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error','আপডেট ব্যর্থ: '.$e->getMessage());
        }
    }

    public function destroy(School $school, Teacher $teacher)
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
            // Delete teacher (cascade will delete user and user_school_roles)
            $teacher->delete();
            return redirect()->back()->with('success','শিক্ষক মুছে ফেলা হয়েছে');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error','মুছতে ব্যর্থ: '.$e->getMessage());
        }
    }

    public function resetPassword(School $school, Teacher $teacher)
    {
        if ($teacher->school_id !== $school->id) abort(404);
        try {
            // Generate new 6-digit password
            $plainPassword = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            // Update user hashed password
            if ($teacher->user) {
                $teacher->user->password = bcrypt($plainPassword);
                $teacher->user->password_changed_at = now();
                $teacher->user->save();
            }
            // Store plain password on teacher profile for principal visibility
            $teacher->plain_password = $plainPassword;
            $teacher->save();
            return redirect()->back()->with('success','পাসওয়ার্ড রিসেট হয়েছে');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error','রিসেট ব্যর্থ: '.$e->getMessage());
        }
    }
}
