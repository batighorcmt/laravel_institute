<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'school_id' => $this->school_id,
            'name' => $this->full_name,
            'name_bn' => $this->full_name_bn,
            'designation' => $this->designation,
            'phone' => $this->phone,
            'photo' => $this->photo,
            'status' => $this->status,
            'joining_date' => optional($this->joining_date)->toDateString(),
        ];
    }
}
