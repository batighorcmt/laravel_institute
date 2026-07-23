<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray($request): array
    {
        $photoUrl = null;
        if ($this->photo) {
            if (! str_starts_with($this->photo, 'http')) {
                $storageUrl = \Illuminate\Support\Facades\Storage::url($this->photo);
                $photoUrl = rtrim(config('app.url'), '/') . '/' . ltrim($storageUrl, '/');
            } else {
                $photoUrl = $this->photo;
            }
        }

        $addressParts = array_filter([
            $this->present_village,
            $this->present_post_office,
            $this->presentThana?->bn_name,
            $this->presentDistrict?->bn_name,
            $this->presentDivision?->bn_name,
        ]);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'school_id' => $this->school_id,
            'name' => $this->full_name,
            'name_bn' => $this->full_name_bn,
            'designation' => $this->designation,
            'phone' => $this->phone,
            'email' => $this->user?->email,
            'photo' => $photoUrl,
            'status' => $this->status,
            'joining_date' => optional($this->joining_date)->toDateString(),
            'present_address' => $addressParts ? implode(', ', $addressParts) : null,
        ];
    }
}
