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
            'student_id' => $st?->id,
            'name' => $st?->full_name,
            'roll' => $en->roll_no,
            'class_name' => $en->schoolClass?->name,
            'section_name' => $en->section?->name,
            'group_name' => $en->group?->name,
            'gender' => $st?->gender,
            'phone' => $st?->phone,
            'photo_url' => $photoUrl,
        ];
    }
}
