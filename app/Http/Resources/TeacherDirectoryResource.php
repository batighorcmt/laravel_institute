<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TeacherDirectoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $photoPath = $this->photo ?: null;
        $photoUrl = null;
        if ($photoPath) {
            // Prefer storage URL mapping when using local/public disk
            if (! str_starts_with($photoPath, 'http')) {
                $storageUrl = Storage::url($photoPath); // e.g. /storage/teachers/photos/...
                $photoUrl = rtrim(config('app.url'), '/') . '/' . ltrim($storageUrl, '/');
            } else {
                $photoUrl = $photoPath;
            }
        }

        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'name' => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')),
            'designation' => $this->designation,
            'phone' => $this->phone,
            'photo_url' => $photoUrl,
        ];
    }
}
