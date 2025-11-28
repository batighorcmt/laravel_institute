<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherDirectoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $photo = $this->photo ?: null; // Assuming already stored relative path
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'name' => trim(($this->first_name ?? '').' '.($this->last_name ?? '')),
            'designation' => $this->designation,
            'phone' => $this->phone,
            'photo' => $photo,
        ];
    }
}
