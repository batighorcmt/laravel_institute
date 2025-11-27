<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'name' => $this->full_name,
            'name_en' => $this->student_name_en,
            'name_bn' => $this->student_name_bn,
            'gender' => $this->gender,
            'date_of_birth' => optional($this->date_of_birth)->toDateString(),
            'age' => $this->age,
            'class_id' => $this->class_id,
            'optional_subject_id' => $this->optional_subject_id,
            'roll' => $this->roll,
            'guardian_phone' => $this->guardian_phone,
            'photo_url' => $this->photo_url,
            'status' => $this->status,
            'admission_date' => optional($this->admission_date)->toDateString(),
        ];
    }
}
