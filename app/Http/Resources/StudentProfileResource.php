<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentProfileResource extends JsonResource
{
    // Resource receives a Student model
    public function toArray($request): array
    {
        $st = $this->resource;
        $photoPath = $st?->photo_url;
        $photoUrl = null;
        if ($photoPath) {
            if (! str_starts_with($photoPath, 'http')) {
                $storageUrl = Storage::url($photoPath);
                $photoUrl = rtrim(config('app.url'), '/') . '/' . ltrim($storageUrl, '/');
            } else {
                $photoUrl = $photoPath;
            }
        }

        $en = $st?->currentEnrollment;

        // Compose present address from parts if available
        $presentParts = array_filter([
            $st?->present_village,
            $st?->present_post_office,
            $st?->present_upazilla,
            $st?->present_district,
        ]);
        $presentAddress = $presentParts ? implode(', ', $presentParts) : ($st?->present_address ?? null);

        // Compose permanent address from parts if available
        $permanentParts = array_filter([
            $st?->permanent_village,
            $st?->permanent_post_office,
            $st?->permanent_upazilla,
            $st?->permanent_district,
        ]);
        $permanentAddress = $permanentParts ? implode(', ', $permanentParts) : ($st?->permanent_address ?? null);

        // Try to include BN variants when fields exist on the model
        $presentAddressBn = $st?->present_address_bn ?? null;
        $permanentAddressBn = $st?->permanent_address_bn ?? null;

        return [
            'id' => $st?->id,
            'student_id' => $st?->student_id,
            'student_code' => $st?->student_id, // Alias for mobile app
            'name' => $st?->full_name,
            'name_en' => $st?->student_name_en,
            'name_bn' => $st?->student_name_bn,
            'gender' => $st?->gender,
            'date_of_birth' => optional($st?->date_of_birth)->toDateString(),
            'dob' => optional($st?->date_of_birth)->toDateString(), // Alias for mobile app
            'phone' => $st?->guardian_phone,
            'email' => $st?->email ?? null,
            'blood_group' => $st?->blood_group ?? null,
            'religion' => $st?->religion ?? null,
            'photo_url' => $photoUrl,
            'class' => $en?->class?->name,
            'section' => $en?->section?->name,
            'group' => $en?->group?->name,
            'roll' => $en?->roll_no,
            'academic_year' => $en?->academicYear?->name,
            'year' => $en?->academicYear?->name,
            'session' => $en?->academicYear?->name, 
            'shift' => $en?->class?->shift?->name,
            'medium' => $en?->class?->medium,
            'optional_subject' => $st?->optionalSubject?->name,

            // Guardian fields (top-level) so mobile clients can easily read them
            'guardian_name' => $st?->guardian_name_en ?? $st?->guardian_name_bn ?? $st?->father_name ?? $st?->mother_name,
            'guardian_name_en' => $st?->guardian_name_en,
            'guardian_name_bn' => $st?->guardian_name_bn,
            'guardian_phone' => $st?->guardian_phone,
            'guardian_relation' => $st?->guardian_relation ?? null,

            // Specific parent fields at top level for picking robustness
            'father_name' => $st?->father_name,
            'father' => $st?->father_name,
            'mother_name' => $st?->mother_name,
            'mother' => $st?->mother_name,
            'father_phone' => null, // Column doesn't exist in students table
            'mother_phone' => null, // Column doesn't exist in students table

            'guardians' => [
                'father_name' => $st?->father_name,
                'father_name_bn' => $st?->father_name_bn,
                'father_phone' => null, // Column doesn't exist
                'mother_name' => $st?->mother_name,
                'mother_name_bn' => $st?->mother_name_bn,
                'mother_phone' => null, // Column doesn't exist
            ],
            // Present / Permanent addresses (composed plus BN if available)
            'present_address' => $presentAddress,
            'present_address_bn' => $presentAddressBn,
            'present_village' => $st?->present_village,
            'present_para_moholla' => $st?->present_para_moholla,
            'present_post_office' => $st?->present_post_office,
            'present_upazilla' => $st?->present_upazilla,
            'present_district' => $st?->present_district,
            
            'permanent_address' => $permanentAddress,
            'permanent_address_bn' => $permanentAddressBn,
            'permanent_village' => $st?->permanent_village,
            'permanent_para_moholla' => $st?->permanent_para_moholla,
            'permanent_post_office' => $st?->permanent_post_office,
            'permanent_upazilla' => $st?->permanent_upazilla,
            'permanent_district' => $st?->permanent_district,

            // Admission & previous fields from students table
            'admission_date' => optional($st?->admission_date)->toDateString(),
            'previous_school' => $st?->previous_school ?? null,
            'pass_year' => $st?->pass_year ?? null,
            'previous_result' => $st?->previous_result ?? null,
            'previous_remarks' => $st?->previous_remarks ?? null,
            'status' => $st?->status ?? null,
            'student_status' => $st?->status ?? null,

            // New fields for comprehensive profile
            'attendance_stats' => $st?->attendance_stats,
            'working_days' => $st?->working_days,
            'enrollment_history' => $st?->relationLoaded('enrollments') ? $st?->enrollments->map(fn($en) => [
                'id' => $en->id,
                'academic_year' => $en->academicYear?->name,
                'class' => $en->class?->name,
                'section' => $en->section?->name,
                'group' => $en->group?->name,
                'roll' => $en->roll_no,
                'status' => $en->status,
            ]) : [],
            'memberships' => $st?->relationLoaded('teams') ? $st?->teams->map(fn($tm) => [
                'id' => $tm->id,
                'name' => $tm->name,
                'status' => $tm->pivot?->status,
                'joined_at' => $tm->pivot?->joined_at,
            ]) : [],
        ];
    }
}
