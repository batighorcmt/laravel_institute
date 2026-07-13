<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\BiometricAttendanceLog;
use App\Models\BiometricDevice;
use App\Models\BiometricDeviceGroup;
use App\Models\BiometricProfile;
use App\Models\BiometricSyncLog;
use App\Models\FingerprintTemplate;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BiometricController extends Controller
{
    // ─── Dashboard ────────────────────────────────────────────────────────────
    public function dashboard(School $school)
    {
        $totalDevices   = BiometricDevice::where('school_id', $school->id)->count();
        $onlineDevices  = BiometricDevice::where('school_id', $school->id)->where('status', 'online')->count();
        $offlineDevices = $totalDevices - $onlineDevices;

        $todayLogs   = BiometricAttendanceLog::where('school_id', $school->id)
                            ->whereDate('punch_time', today())->count();
        $pendingSync = BiometricAttendanceLog::where('school_id', $school->id)
                            ->where('sync_status', 'pending')->count();
        $failedSync  = BiometricAttendanceLog::where('school_id', $school->id)
                            ->where('sync_status', 'failed')->count();

        $devices     = BiometricDevice::where('school_id', $school->id)->with('deviceGroup')->get();

        $recentLogs  = BiometricSyncLog::where('school_id', $school->id)
                            ->with('device')->latest()->take(10)->get();

        $unassignedCount = BiometricProfile::where('school_id', $school->id)
                            ->where('user_type', 'unassigned')->count();

        return view('principal.biometric.dashboard', compact(
            'school', 'totalDevices', 'onlineDevices', 'offlineDevices',
            'todayLogs', 'pendingSync', 'failedSync', 'devices', 'recentLogs', 'unassignedCount'
        ));
    }

    // ─── Settings ─────────────────────────────────────────────────────────────
    public function settings(School $school)
    {
        return view('principal.biometric.settings', compact('school'));
    }

    // ─── Token Management ─────────────────────────────────────────────────────
    public function generateToken(School $school)
    {
        $school->update([
            'agent_token' => \Illuminate\Support\Str::random(40),
        ]);

        return back()->with('success', 'নতুন এজেন্ট টোকেন সফলভাবে জেনারেট করা হয়েছে।');
    }

    public function syncUsersToDevice(School $school, Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|exists:biometric_devices,id',
            'scope'     => 'required|in:all,class,student',
        ]);

        BiometricSyncLog::create([
            'school_id'   => $school->id,
            'device_id'   => $data['device_id'],
            'action'      => 'sync_users',
            'record_type' => $data['scope'],
            'status'      => 'queued',
            'message'     => "ব্যবহারকারী সিঙ্ক কমান্ড কিউ করা হয়েছে (scope: {$data['scope']})",
        ]);

        return response()->json(['message' => 'সিঙ্ক কমান্ড পাঠানো হয়েছে। Local Agent শীঘ্রই প্রক্রিয়া করবে।']);
    }

    // ─── Phase 6: Reports ────────────────────────────────────────────────────
    public function reportsIndex(School $school)
    {
        return view('principal.biometric.reports.index', compact('school'));
    }

    public function dailyReport(School $school, Request $request)
    {
        $date    = $request->input('date', today()->toDateString());
        $deviceId = $request->input('device_id');

        $logsQuery = BiometricAttendanceLog::where('school_id', $school->id)
                        ->whereDate('punch_time', $date)
                        ->with('device');

        if ($deviceId) {
            $logsQuery->where('device_id', $deviceId);
        }

        $logs = $logsQuery->orderBy('punch_time')->get()->map(function ($log) use ($school) {
            $log->student = Student::where('school_id', $school->id)
                                ->where('biometric_id', $log->biometric_id)
                                ->with('class')
                                ->first();
            $log->teacher = \App\Models\Teacher::where('school_id', $school->id)
                                ->where('biometric_id', $log->biometric_id)
                                ->first();
            return $log;
        });

        $devices = BiometricDevice::where('school_id', $school->id)->get();

        // Summary counts
        $present = $logs->where('sync_status', 'processed')->unique('biometric_id')->count();
        $pending = $logs->where('sync_status', 'pending')->count();

        return view('principal.biometric.reports.daily',
            compact('school', 'logs', 'date', 'devices', 'deviceId', 'present', 'pending'));
    }

    public function syncHistory(School $school, Request $request)
    {
        $logs = BiometricSyncLog::where('school_id', $school->id)
                    ->with('device')->latest()->paginate(50);

        return view('principal.biometric.reports.sync_history', compact('school', 'logs'));
    }

    // ─── Phase 6: Unassigned Profiles ────────────────────────────────────────
    public function unassignedProfiles(School $school, Request $request)
    {
        $search = $request->input('search');

        // Auto-match any unassigned profiles to students or teachers when possible.
        $toMatch = BiometricProfile::where('school_id', $school->id)
            ->where('user_type', 'unassigned')
            ->whereNotNull('biometric_id')
            ->where('biometric_id', '!=', '')
            ->pluck('biometric_id', 'id');

        if ($toMatch->isNotEmpty()) {
            foreach ($toMatch as $id => $bId) {
                // Skip if already linked (defensive)
                $profile = BiometricProfile::find($id);
                if (!$profile || $profile->user_type !== 'unassigned') continue;

                // Try student first
                $student = Student::where('school_id', $school->id)
                            ->where('biometric_id', $bId)
                            ->first();
                if ($student) {
                    $profile->update(['user_type' => 'student', 'student_id' => $student->id, 'status' => 'active']);
                    continue;
                }

                // Try teacher
                $teacher = Teacher::where('school_id', $school->id)
                            ->where('biometric_id', $bId)
                            ->first();
                if ($teacher) {
                    $profile->update(['user_type' => 'teacher', 'teacher_id' => $teacher->id, 'status' => 'active']);
                    continue;
                }
            }
        }

        $profiles = BiometricProfile::where('school_id', $school->id)
            ->where('user_type', 'unassigned')
            ->with('templates')
            ->when($search, fn($q) => $q->where('biometric_id', 'like', "%{$search}%"))
            ->latest()
            ->paginate(30);

        $students = Student::where('school_id', $school->id)
            ->active()
            ->whereNull('biometric_id')
            ->orderBy('student_name_en')
            ->get(['id', 'student_name_en', 'student_name_bn', 'student_id']);

        $teachers = Teacher::where('school_id', $school->id)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'phone']);

        $totalUnassigned = BiometricProfile::where('school_id', $school->id)
            ->where('user_type', 'unassigned')->count();

        return view('principal.biometric.profiles.unassigned',
            compact('school', 'profiles', 'students', 'teachers', 'search', 'totalUnassigned'));
    }

    public function linkProfile(School $school, Request $request, BiometricProfile $profile)
    {
        abort_if($profile->school_id !== $school->id, 403);

        $data = $request->validate([
            'user_type'  => 'required|in:student,teacher',
            'user_id'    => 'required|integer',
        ]);

        if ($data['user_type'] === 'student') {
            $user = Student::where('school_id', $school->id)->findOrFail($data['user_id']);
            // Ensure no duplicate
            if (Student::where('school_id', $school->id)->where('biometric_id', $profile->biometric_id)->where('id', '!=', $user->id)->exists()) {
                return back()->withErrors(['user_id' => 'এই বায়োমেট্রিক আইডি ইতিমধ্যে অন্য শিক্ষার্থীর সাথে যুক্ত।']);
            }
            $user->update(['biometric_id' => $profile->biometric_id]);
            $profile->update(['user_type' => 'student', 'student_id' => $user->id, 'status' => 'active']);
        } else {
            $user = Teacher::where('school_id', $school->id)->findOrFail($data['user_id']);
            $profile->update(['user_type' => 'teacher', 'teacher_id' => $user->id, 'status' => 'active']);
        }

        return back()->with('success', 'বায়োমেট্রিক প্রোফাইল সফলভাবে লিংক করা হয়েছে।');
    }

    public function deleteProfile(School $school, BiometricProfile $profile)
    {
        abort_if($profile->school_id !== $school->id, 403);
        $profile->templates()->delete();
        $profile->delete();

        return back()->with('success', 'অজানা প্রোফাইল ও সকল ফিঙ্গারপ্রিন্ট টেমপ্লেট মুছে ফেলা হয়েছে।');
    }

    // ─── Phase 6: Live Monitor ────────────────────────────────────────────────
    public function liveMonitor(School $school)
    {
        $devices = BiometricDevice::where('school_id', $school->id)
            ->with('deviceGroup')
            ->orderBy('status')
            ->get();

        $todayTotal  = BiometricAttendanceLog::where('school_id', $school->id)->whereDate('punch_time', today())->count();
        $hourlyStats = BiometricAttendanceLog::where('school_id', $school->id)
            ->whereDate('punch_time', today())
            ->selectRaw('HOUR(punch_time) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        $recentPunches = BiometricAttendanceLog::where('school_id', $school->id)
            ->with('device')
            ->latest('punch_time')
            ->take(20)
            ->get()
            ->map(function ($log) use ($school) {
                $log->student = Student::where('school_id', $school->id)
                    ->where('biometric_id', $log->biometric_id)->first(['student_name_en', 'student_id']);
                return $log;
            });

        $unassignedCount = BiometricProfile::where('school_id', $school->id)->where('user_type', 'unassigned')->count();

        $agentLastSeen = $school->agent_last_seen ? Carbon::parse($school->agent_last_seen)->diffForHumans() : '—';
        $agentIsOnline = $this->isAgentOnline($school);
        $agentOnlineDuration = $this->formatAgentOnlineDuration($school, $agentIsOnline);

        return view('principal.biometric.monitor',
            compact('school', 'devices', 'todayTotal', 'hourlyStats', 'recentPunches', 'unassignedCount', 'agentLastSeen', 'agentIsOnline', 'agentOnlineDuration'));
    }

    public function monitorStatus(School $school)
    {
        $devices = BiometricDevice::where('school_id', $school->id)
            ->get(['id', 'device_name', 'status', 'ip_address', 'last_seen', 'location']);

        $todayTotal = BiometricAttendanceLog::where('school_id', $school->id)->whereDate('punch_time', today())->count();
        $lastPunch  = BiometricAttendanceLog::where('school_id', $school->id)->latest('punch_time')->value('punch_time');

        $agentIsOnline = $this->isAgentOnline($school);

        return response()->json([
            'devices'     => $devices->map(fn($d) => [
                'id'        => $d->id,
                'name'      => $d->device_name,
                'status'    => $d->status,
                'ip'        => $d->ip_address,
                'location'  => $d->location,
                'last_seen' => $d->last_seen ? Carbon::parse($d->last_seen)->diffForHumans() : '—',
            ]),
            'today_total' => $todayTotal,
            'last_punch'  => $lastPunch ? Carbon::parse($lastPunch)->diffForHumans() : '—',
            'agent_last_seen' => $school->agent_last_seen ? Carbon::parse($school->agent_last_seen)->diffForHumans() : '—',
            'agent_is_online' => $agentIsOnline,
            'agent_online_duration' => $this->formatAgentOnlineDuration($school, $agentIsOnline),
        ]);
    }

    private function isAgentOnline(School $school): bool
    {
        return (bool) ($school->agent_last_seen && Carbon::parse($school->agent_last_seen)->diffInMinutes(now()) <= 5);
    }

    private function formatAgentOnlineDuration(School $school, bool $isOnline): string
    {
        if (!$isOnline || !$school->agent_online_since) {
            return '—';
        }

        $diff = Carbon::parse($school->agent_online_since)->diff(now());
        $parts = [];

        if ($diff->days > 0) {
            $parts[] = $this->toBanglaNumber($diff->days) . ' দিন';
        }
        if ($diff->h > 0) {
            $parts[] = $this->toBanglaNumber($diff->h) . ' ঘন্টা';
        }
        if ($diff->i > 0) {
            $parts[] = $this->toBanglaNumber($diff->i) . ' মিনিট';
        }
        if ($diff->s > 0 && empty($parts)) {
            $parts[] = $this->toBanglaNumber($diff->s) . ' সেকেন্ড';
        }

        return empty($parts) ? 'শূন্য সেকেন্ড' : implode(' ', $parts);
    }

    private function toBanglaNumber($value): string
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        return str_replace($en, $bn, (string) $value);
    }
    // ─── Devices & Enrollment ──────────────────────────────────────────────────
    public function devicesIndex(School $school)
    {
        $devices = BiometricDevice::where('school_id', $school->id)->with('deviceGroup')->get();
        return view('principal.biometric.devices.index', compact('school', 'devices'));
    }

    public function enrollmentIndex(School $school, Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');

        $studentsQuery = Student::where('school_id', $school->id)
            ->active()
            ->with(['class', 'section']);

        if ($search) {
            $studentsQuery->where(function($q) use ($search) {
                $q->where('student_name_en', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('biometric_id', 'like', "%{$search}%");
            });
        }

        if ($filter === 'enrolled') {
            $studentsQuery->whereNotNull('biometric_id');
        } elseif ($filter === 'pending') {
            $studentsQuery->whereNull('biometric_id');
        }

        $students = $studentsQuery->paginate(30);

        $totalEnrolled = Student::where('school_id', $school->id)->active()->whereNotNull('biometric_id')->count();
        $totalPending  = Student::where('school_id', $school->id)->active()->whereNull('biometric_id')->count();

        $devices = BiometricDevice::where('school_id', $school->id)->get();

        return view('principal.biometric.enrollment.index', compact(
            'school', 'students', 'search', 'filter', 'totalEnrolled', 'totalPending', 'devices'
        ));
    }
}
