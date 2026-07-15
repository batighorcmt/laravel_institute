<?php

namespace App\Http\Controllers\Api\Biometric;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BiometricDevice;
use App\Models\BiometricProfile;
use App\Models\FingerprintTemplate;
use App\Models\FaceTemplate;

class BackupSnapshotController extends Controller
{
    /**
     * Upload a full disaster-recovery backup batch (fingers + card + face per user,
     * including zero-fingerprint users) from the agent to the cloud. Deliberately
     * separate from BiometricSyncController::uploadTemplates()/downloadTemplates() -
     * those endpoints are used by the currently-deployed production agent for routine
     * "replace with latest" sync and have a different payload shape/contract. This
     * endpoint reuses the same underlying tables (biometric_profiles, fingerprint_templates,
     * face_templates) via idempotent updateOrCreate calls, so it's additive and safe
     * alongside the existing sync flow rather than a parallel data store.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'school_id' => 'required|integer',
            'device_serial' => 'required|string',
            'records' => 'required|array',
            'records.*.biometric_id' => 'required|string',
            'records.*.name' => 'nullable|string',
            'records.*.privilege' => 'nullable|integer',
            'records.*.card_number' => 'nullable|string',
            'records.*.face_data' => 'nullable|string',
            'records.*.fingers' => 'nullable|array',
            'records.*.fingers.*.finger_index' => 'required_with:records.*.fingers|integer',
            'records.*.fingers.*.template_data' => 'required_with:records.*.fingers|string',
        ]);

        $device = BiometricDevice::where('school_id', $request->school_id)
            ->where('serial_number', $request->device_serial)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        $savedUsers = 0;
        foreach ($request->records as $record) {
            $normalizedId = $this->normalizeBiometricId($record['biometric_id']);
            if ($normalizedId === '') continue;

            $profile = BiometricProfile::firstOrCreate(
                ['school_id' => $request->school_id, 'biometric_id' => $normalizedId],
                ['user_type' => 'unassigned']
            );

            if (!empty($record['card_number'])) {
                $profile->card_number = $record['card_number'];
            }

            foreach (($record['fingers'] ?? []) as $finger) {
                FingerprintTemplate::updateOrCreate(
                    [
                        'biometric_profile_id' => $profile->id,
                        'finger_name' => 'Finger ' . $finger['finger_index'],
                    ],
                    [
                        'template_data' => $finger['template_data'],
                        'device_source' => $device->serial_number,
                    ]
                );
            }

            if (!empty($record['face_data'])) {
                FaceTemplate::updateOrCreate(
                    ['biometric_profile_id' => $profile->id],
                    [
                        'template_data' => $record['face_data'],
                        'device_source' => $device->serial_number,
                    ]
                );
            }

            $profile->finger_count = $profile->templates()->count();
            $profile->save();
            $savedUsers++;
        }

        return response()->json(['message' => "$savedUsers user backup record(s) saved"]);
    }

    /**
     * Download the current full backup (fingers + card + face per user) for a device's
     * school, reconstructed from the live tables rather than a separate snapshot store -
     * the agent's own local SQLite backup already provides point-in-time history.
     */
    public function download(Request $request)
    {
        $request->validate([
            'school_id' => 'required|integer',
            'device_serial' => 'required|string',
        ]);

        $device = BiometricDevice::where('school_id', $request->school_id)
            ->where('serial_number', $request->device_serial)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        $profiles = BiometricProfile::where('school_id', $request->school_id)
            ->where('status', 'active')
            ->with(['templates', 'faceTemplates'])
            ->get();

        $records = [];
        foreach ($profiles as $profile) {
            $name = '';
            if ($profile->user_type === 'student' && $profile->student) {
                $name = $profile->student->student_name_en ?? $profile->student->student_name_bn ?? '';
            } elseif ($profile->user_type === 'teacher' && $profile->teacher) {
                $name = trim($profile->teacher->first_name . ' ' . $profile->teacher->last_name);
            }

            $fingers = [];
            foreach ($profile->templates as $template) {
                $fIndex = 0;
                if (preg_match('/Finger (\d+)/', (string) $template->finger_name, $matches)) {
                    $fIndex = (int) $matches[1];
                }
                $fingers[] = [
                    'finger_index' => $fIndex,
                    'template_data' => $template->template_data,
                ];
            }

            $records[] = [
                'biometric_id' => $profile->biometric_id,
                'name' => $name,
                'privilege' => 0,
                'card_number' => $profile->card_number ?? '',
                'face_data' => optional($profile->faceTemplates->first())->template_data ?? '',
                'fingers' => $fingers,
            ];
        }

        return response()->json(['records' => $records]);
    }

    private function normalizeBiometricId(string $biometricId): string
    {
        return trim(preg_replace('/[^0-9]/', '', $biometricId));
    }
}
