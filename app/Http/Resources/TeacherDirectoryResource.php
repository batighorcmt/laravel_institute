<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherDirectoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $perArr = [];
        if ($this->permanent_village) {
            $perArr[] = 'গ্রাম: '.$this->permanent_village;
        }
        if ($this->permanent_post_office) {
            $perArr[] = 'ডাকঘর: '.$this->permanent_post_office;
        }
        if ($this->permanentThana) {
            $perArr[] = 'উপজেলা: '.($this->permanentThana->bn_name ?? $this->permanentThana->name);
        }
        if ($this->permanentDistrict) {
            $perArr[] = 'জেলা: '.($this->permanentDistrict->bn_name ?? $this->permanentDistrict->name);
        }

        return [
            'id' => $this->id,
            'teacher_id' => $this->id, // Export for frontend use
            'serial_number' => $this->serial_number,
            'name' => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')),
            'initials' => $this->initials,
            'designation' => $this->designation,
            'phone' => $this->phone,
            'email' => $this->user?->email,
            'photo_url' => $this->photo_url,
            'permanent_address' => $perArr ? implode(', ', $perArr) : null,
        ];
    }
}
