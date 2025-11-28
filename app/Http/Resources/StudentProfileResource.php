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
        return [
            'id' => $st?->id,
            'name' => $st?->full_name,
            'gender' => $st?->gender,
            'date_of_birth' => optional($st?->date_of_birth)->toDateString(),
            'phone' => $st?->phone,
            'photo_url' => $photoUrl,
            'class' => $en?->class?->name,
            'section' => $en?->section?->name,
            'group' => $en?->group?->name,
            'roll' => $en?->roll_no,
            'guardians' => [
                'father_name' => $st?->father_name,
                'father_phone' => $st?->father_phone,
                'mother_name' => $st?->mother_name,
                'mother_phone' => $st?->mother_phone,
            ],
            'address' => [
                'present_address' => $st?->present_address,
                'permanent_address' => $st?->permanent_address,
            ],
        ];
    }
}
