<?php

namespace App\Http\Controllers\Api\Biometric;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BiometricDevice;
use App\Models\DeviceHeartbeat;
use App\Models\BiometricAttendanceLog;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Jobs\ProcessBiometricPunchJob;

class BiometricSyncController extends Controller
{
    /**
     * Receive heartbeats from devices via the Local Agent.
     */
    public function heartbeat(Request $request)
    {
        $request->validate([
            'school_id' => 'required|integer',
            'device_serial' => 'required|string',
            'status' => 'required|string',
            'ip_address' => 'nullable|string',
            'device_name' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $deviceName = $request->device_name ?: ('Device ' . $request->device_serial);

        $device = BiometricDevice::firstOrCreate(
            ['school_id' => $request->school_id, 'serial_number' => $request->device_serial],
            ['device_name' => $deviceName, 'ip_address' => $request->ip_address, 'status' => 'offline', 'location' => $request->location]
        );

        $updateData = [
            'status' => $request->status,
            'ip_address' => $request->ip_address,
            'last_seen' => now(),
        ];
        
        if ($request->filled('device_name')) {
            $updateData['device_name'] = $request->device_name;
        }
        if ($request->filled('location')) {
            $updateData['location'] = $request->location;
        }

        $device->update($updateData);

        DeviceHeartbeat::create([
            'device_id' => $device->id,
            'status' => $request->status,
            'ip_address' => $request->ip_address,
            'last_check' => now(),
        ]);

        School::where('id', $request->school_id)->update(['agent_last_seen' => now()]);

        return response()->json(['message' => 'Heartbeat logged', 'device_id' => $device->id]);
    }

    /**
     * Receive attendance punch logs from the Local Agent.
     */
    public function syncAttendance(Request $request)
    {
        $request->validate([
            'school_id' => 'required|integer',
            'device_serial' => 'required|string',
            'logs' => 'required|array',
            'logs.*.biometric_id' => 'required|string',
            'logs.*.punch_time' => 'required|date',
            'logs.*.punch_type' => 'nullable|string',
        ]);

        $device = BiometricDevice::where('school_id', $request->school_id)
            ->where('serial_number', $request->device_serial)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        foreach ($request->logs as $log) {
            $attendanceLog = BiometricAttendanceLog::create([
                'school_id' => $request->school_id,
                'device_id' => $device->id,
                'biometric_id' => $log['biometric_id'],
                'punch_time' => $log['punch_time'],
                'punch_type' => $log['punch_type'] ?? null,
                'sync_status' => 'pending'
            ]);

            // Dispatch job to process the log (entry, exit, late calc)
            ProcessBiometricPunchJob::dispatch($attendanceLog);
        }

        $device->update(['last_sync_time' => now()]);

        return response()->json(['message' => 'Attendance logs received and queued for processing']);
    }
    /**
     * Upload templates from the device to the Web DB.
     */
    public function uploadTemplates(Request $request)
    {
        $request->validate([
            'school_id' => 'required|integer',
            'device_serial' => 'required|string',
            'templates' => 'required|array',
            'templates.*.biometric_id' => 'required|string',
            'templates.*.finger_index' => 'required|integer',
            'templates.*.template_data' => 'required|string',
            'templates.*.privilege' => 'nullable|integer',
            'templates.*.name' => 'nullable|string',
        ]);

        $device = BiometricDevice::where('school_id', $request->school_id)
            ->where('serial_number', $request->device_serial)
            ->first();

        if (!$device) return response()->json(['message' => 'Device not found'], 404);

        $insertedCount = 0;
        foreach ($request->templates as $tpl) {
            // Find or create profile
            $profile = \App\Models\BiometricProfile::firstOrCreate(
                ['school_id' => $request->school_id, 'biometric_id' => $tpl['biometric_id']],
                ['user_type' => 'unassigned']
            );

            // Update template
            \App\Models\FingerprintTemplate::updateOrCreate(
                [
                    'biometric_profile_id' => $profile->id,
                    'finger_name' => 'Finger ' . $tpl['finger_index']
                ],
                [
                    'template_data' => $tpl['template_data'],
                    'device_source' => $device->serial_number
                ]
            );

            // Update profile finger count
            $profile->finger_count = $profile->templates()->count();
            $profile->save();
            $insertedCount++;
        }

        return response()->json(['message' => "$insertedCount templates uploaded successfully"]);
    }

    /**
     * Download templates from Web DB to the device.
     */
    public function downloadTemplates(Request $request)
    {
        $request->validate([
            'school_id' => 'required|integer',
            'device_serial' => 'required|string',
        ]);

        $device = BiometricDevice::where('school_id', $request->school_id)
            ->where('serial_number', $request->device_serial)
            ->first();

        if (!$device) return response()->json(['message' => 'Device not found'], 404);

        // Fetch all templates for the school (in a production system, this should be optimized to fetch only new/updated templates for this specific device)
        $profiles = \App\Models\BiometricProfile::where('school_id', $request->school_id)
            ->where('status', 'active')
            ->with('templates')
            ->get();

        $downloadList = [];
        foreach ($profiles as $profile) {
            $name = "";
            if ($profile->user_type == 'student' && $profile->student) {
                $name = $profile->student->student_name_en ?? $profile->student->student_name_bn ?? '';
            } elseif ($profile->user_type == 'teacher' && $profile->teacher) {
                $name = trim($profile->teacher->first_name . ' ' . $profile->teacher->last_name);
            }

            foreach ($profile->templates as $template) {
                // Determine finger index from 'Finger X'
                $fIndex = 0;
                if (preg_match('/Finger (\d+)/', $template->finger_name, $matches)) {
                    $fIndex = (int)$matches[1];
                }

                $downloadList[] = [
                    'biometric_id' => $profile->biometric_id,
                    'finger_index' => $fIndex,
                    'template_data' => $template->template_data,
                    'privilege' => 0,
                    'name' => $name
                ];
            }
        }

        return response()->json([
            'templates' => $downloadList
        ]);
    }

    /**
     * Fetch all users (Students and Teachers) with their biometric IDs for the Agent
     */
    public function getUsers(Request $request)
    {
        $request->validate([
            'school_id' => 'required|integer',
        ]);

        $schoolId = $request->school_id;

        $students = Student::where('school_id', $schoolId)
            ->active()
            ->get(['id', 'biometric_id', 'student_id', 'student_name_en', 'student_name_bn'])
            ->map(function ($s) {
                if (empty($s->biometric_id) && !empty($s->student_id)) {
                    // Extract only numbers from student_id
                    $numericId = preg_replace('/[^0-9]/', '', $s->student_id);
                    if (!empty($numericId)) {
                        $s->biometric_id = $numericId;
                        $s->save();
                    }
                }
                
                return [
                    'biometric_id' => $s->biometric_id ?? (string)$s->id,
                    'name' => $s->student_name_en ?? $s->student_name_bn,
                    'role' => 'Student'
                ];
            });

        $teachers = Teacher::where('school_id', $schoolId)
            ->where('status', 'active')
            ->get(['id', 'biometric_id', 'first_name', 'last_name'])
            ->map(function ($t) {
                if (empty($t->biometric_id)) {
                    $t->biometric_id = (string)$t->id;
                    $t->save();
                }
                
                return [
                    'biometric_id' => $t->biometric_id,
                    'name' => trim($t->first_name . ' ' . $t->last_name),
                    'role' => 'Teacher'
                ];
            });

        $users = $students->concat($teachers)->values();

        return response()->json(['users' => $users]);
    }
}
