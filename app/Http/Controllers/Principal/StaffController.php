<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Role;
use App\Models\School;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\UserSchoolRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(School $school)
    {
        return view('principal.staff.index', compact('school'));
    }

    public function data(School $school)
    {
        $staff = StaffMember::where('school_id', $school->id)
            ->with(['designationRef:id,name_en,name_bn', 'user:id,username'])
            ->orderByRaw('COALESCE(serial_number, 999999)')
            ->orderBy('id')
            ->get()
            ->map(fn (StaffMember $s) => $this->transform($s));

        $designations = Designation::orderBy('name_bn')->get(['id', 'name_en', 'name_bn']);

        return response()->json([
            'staff' => $staff,
            'designations' => $designations,
        ]);
    }

    public function store(Request $request, School $school)
    {
        $data = $this->validated($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('staff/photos', 'public');
        }

        DB::beginTransaction();
        try {
            $staff = StaffMember::create($data + ['school_id' => $school->id]);
            $this->createLoginFor($staff, $school);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'message' => 'কর্মচারী সফলভাবে যুক্ত করা হয়েছে। লগইন তথ্য: ইউজারনেম '.$staff->user->username.', পাসওয়ার্ড '.$staff->plain_password,
            'staff' => $this->transform($staff->load(['designationRef','user'])),
        ]);
    }

    /**
     * Backfill a login account for a staff member added before login
     * support existed (or whose account was somehow never created).
     */
    public function createLogin(School $school, StaffMember $staffMember)
    {
        abort_unless($staffMember->school_id === $school->id, 404);

        if ($staffMember->user_id) {
            return response()->json(['message' => 'এই কর্মচারীর ইতিমধ্যে লগইন একাউন্ট আছে।'], 422);
        }

        DB::beginTransaction();
        try {
            $this->createLoginFor($staffMember, $school);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'message' => 'লগইন তৈরি হয়েছে। ইউজারনেম: '.$staffMember->user->username.', পাসওয়ার্ড: '.$staffMember->plain_password,
            'staff' => $this->transform($staffMember->fresh()->load(['designationRef','user'])),
        ]);
    }

    /**
     * Create a User + UserSchoolRole(staff) for $staffMember and attach
     * them, mirroring TeacherController::store()'s account-provisioning
     * flow (username pattern, random password, plain_password kept so the
     * principal can view/print the generated credentials once).
     */
    protected function createLoginFor(StaffMember $staffMember, School $school): void
    {
        $staffRole = Role::where('name', Role::STAFF)->firstOrFail();

        $schoolCode = $school->code;
        $counter = 1;
        $existingUsernames = User::where('username', 'LIKE', $schoolCode.'S%')
            ->whereNotNull('username')
            ->pluck('username')
            ->map(function ($username) use ($schoolCode) {
                $num = str_replace($schoolCode.'S', '', $username);
                return is_numeric($num) ? (int) $num : 0;
            })
            ->filter()
            ->toArray();
        if (! empty($existingUsernames)) {
            $counter = max($existingUsernames) + 1;
        }
        $username = $schoolCode.'S'.str_pad($counter, 3, '0', STR_PAD_LEFT);
        while (User::where('username', $username)->exists()) {
            $counter++;
            $username = $schoolCode.'S'.str_pad($counter, 3, '0', STR_PAD_LEFT);
        }

        $plainPassword = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $email = $staffMember->email ?: (uniqid('s_').'@example.com');

        $user = User::create([
            'name' => $staffMember->full_name,
            'username' => $username,
            'email' => $email,
            'password' => bcrypt($plainPassword),
        ]);

        UserSchoolRole::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'role_id' => $staffRole->id,
            'status' => 'active',
        ]);

        $staffMember->update([
            'user_id' => $user->id,
            'plain_password' => $plainPassword,
        ]);
    }

    public function update(Request $request, School $school, StaffMember $staffMember)
    {
        abort_unless($staffMember->school_id === $school->id, 404);

        $data = $this->validated($request);

        if ($request->hasFile('photo')) {
            if ($staffMember->photo) {
                Storage::disk('public')->delete($staffMember->photo);
            }
            $data['photo'] = $request->file('photo')->store('staff/photos', 'public');
        }

        $staffMember->update($data);

        return response()->json([
            'message' => 'কর্মচারীর তথ্য আপডেট হয়েছে।',
            'staff' => $this->transform($staffMember->fresh()->load(['designationRef','user'])),
        ]);
    }

    public function destroy(School $school, StaffMember $staffMember)
    {
        abort_unless($staffMember->school_id === $school->id, 404);

        if ($staffMember->photo) {
            Storage::disk('public')->delete($staffMember->photo);
        }
        $staffMember->delete();

        return response()->json(['message' => 'কর্মচারী মুছে ফেলা হয়েছে।']);
    }

    public function print(Request $request, School $school)
    {
        $query = StaffMember::where('school_id', $school->id)->with('designationRef');

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->integer('designation_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $staff = $query->orderByRaw('COALESCE(serial_number, 999999)')->orderBy('id')->get();

        $printTitle = $school->name_bn ?: $school->name;
        $printSubtitle = 'কর্মচারী তালিকা';
        if ($request->filled('designation_id')) {
            $designation = Designation::find($request->integer('designation_id'));
            if ($designation) {
                $printSubtitle .= ' — পদবী: '.($designation->name_bn ?: $designation->name_en);
            }
        }

        $columns = $request->input('columns', ['col-photo', 'col-name-bn', 'col-designation', 'col-mobile']);
        $lang = $request->input('lang', 'bn');

        return view('principal.staff.print', compact('school', 'staff', 'printTitle', 'printSubtitle', 'columns', 'lang'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'first_name_bn' => ['nullable', 'string', 'max:191'],
            'last_name_bn' => ['nullable', 'string', 'max:191'],
            'designation_id' => ['nullable', Rule::exists('designations', 'id')],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date'],
            'joining_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'serial_number' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'show_on_website' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(StaffMember $staff): array
    {
        return [
            'id' => $staff->id,
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'first_name_bn' => $staff->first_name_bn,
            'last_name_bn' => $staff->last_name_bn,
            'full_name' => $staff->full_name,
            'full_name_bn' => $staff->full_name_bn,
            'designation_id' => $staff->designation_id,
            'designation_label' => $staff->designationRef ? ($staff->designationRef->name_bn ?: $staff->designationRef->name_en) : null,
            'phone' => $staff->phone,
            'email' => $staff->email,
            'address' => $staff->address,
            'date_of_birth' => $staff->date_of_birth?->format('Y-m-d'),
            'joining_date' => $staff->joining_date?->format('Y-m-d'),
            'photo_url' => $staff->photo_url,
            'serial_number' => $staff->serial_number,
            'status' => $staff->status,
            'show_on_website' => $staff->show_on_website,
            'has_login' => (bool) $staff->user_id,
            'username' => $staff->user?->username,
            'plain_password' => $staff->plain_password,
        ];
    }

    public function resetPassword(School $school, StaffMember $staffMember)
    {
        abort_unless($staffMember->school_id === $school->id, 404);

        if (! $staffMember->user_id) {
            return response()->json(['message' => 'এই কর্মচারীর কোনো লগইন একাউন্ট নেই।'], 422);
        }

        try {
            $plainPassword = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $staffMember->user->password = bcrypt($plainPassword);
            $staffMember->user->password_changed_at = now();
            $staffMember->user->save();

            $staffMember->plain_password = $plainPassword;
            $staffMember->save();

            return response()->json([
                'message' => 'পাসওয়ার্ড রিসেট হয়েছে। নতুন পাসওয়ার্ড: '.$plainPassword,
                'staff' => $this->transform($staffMember->fresh()->load(['designationRef', 'user'])),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'রিসেট ব্যর্থ: '.$e->getMessage()], 500);
        }
    }
}
