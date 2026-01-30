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
            'name' => $st?->full_name,
            'gender' => $st?->gender,
            'date_of_birth' => optional($st?->date_of_birth)->toDateString(),
            'phone' => $st?->phone,
            'email' => $st?->email ?? null,
            'religion' => $st?->religion ?? null,
            'photo_url' => $photoUrl,
            'class' => $en?->class?->name,
            'section' => $en?->section?->name,
            'group' => $en?->group?->name,
            'roll' => $en?->roll_no,
            // Guardian fields (top-level) so mobile clients can easily read them
            'guardian_name' => $st?->guardian_name_en ?? $st?->guardian_name_bn ?? $st?->father_name ?? $st?->mother_name,
            'guardian_phone' => $st?->guardian_phone ?? $st?->father_phone ?? $st?->mother_phone,
            'guardian_relation' => $st?->guardian_relation ?? null,
            'guardians' => [
                'father_name' => $st?->father_name,
                'father_phone' => $st?->father_phone,
                'mother_name' => $st?->mother_name,
                'mother_phone' => $st?->mother_phone,
            ],
            // Present / Permanent addresses (composed plus BN if available)
            'present_address' => $presentAddress,
            'present_address_bn' => $presentAddressBn,
            'permanent_address' => $permanentAddress,
            'permanent_address_bn' => $permanentAddressBn,

            // Admission & previous fields from students table
            'admission_date' => optional($st?->admission_date)->toDateString(),
            'previous_school' => $st?->previous_school ?? null,
            'pass_year' => $st?->pass_year ?? null,
            'previous_result' => $st?->previous_result ?? null,
            'previous_remarks' => $st?->previous_remarks ?? null,
            'status' => $st?->status ?? null,
        ];
    }
}
