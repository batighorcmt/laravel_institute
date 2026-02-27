<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentDirectoryResource extends JsonResource
{
    // Resource will receive a StudentEnrollment model
    public function toArray($request): array
    {
        $en = $this->resource; // StudentEnrollment
        $st = $en->student;
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
        return [
            'id' => $st?->id,
            'student_id' => $st?->student_id,
            'name' => $st?->full_name,
            'roll' => $en->roll_no,
            'class' => $en->class?->name,
            'section' => $en->section?->name,
            'group' => $en->group?->name,
            'gender' => $st?->gender,
            'date_of_birth' => $st?->date_of_birth?->format('Y-m-d'),
            'religion' => $st?->religion,
            'phone' => $st?->guardian_phone,
            'photo_url' => $photoUrl,
            'father_name' => $st?->father_name,
            'father_name_bn' => $st?->father_name_bn,
            'mother_name' => $st?->mother_name,
            'mother_name_bn' => $st?->mother_name_bn,
            'present_village' => $st?->present_village,
            'present_post_office' => $st?->present_post_office,
            'present_upazilla' => $st?->present_upazilla,
            'present_district' => $st?->present_district,
            'permanent_village' => $st?->permanent_village,
            'permanent_para_moholla' => $st?->permanent_para_moholla,
            'permanent_post_office' => $st?->permanent_post_office,
            'permanent_upazilla' => $st?->permanent_upazilla,
            'permanent_district' => $st?->permanent_district,
            'previous_school' => $st?->previous_school,
            'pass_year' => $st?->pass_year,
            'previous_result' => $st?->previous_result,
            'previous_remarks' => $st?->previous_remarks,
            'admission_date' => optional($st?->admission_date)->toDateString(),
            'status' => $st?->status,
            'school_name' => $st?->school?->name,
            'school_name_bn' => $st?->school?->name_bn,
            'optional_subject' => $st?->optionalSubject?->name,
            'academic_year' => $en->academicYear?->name,
            'year' => $en->academicYear?->name,
        ];
    }
}
