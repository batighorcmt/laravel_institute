<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherLeaveResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'teacher_id' => $this->teacher_id,
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'type' => $this->type,
            'reason' => $this->reason,
            'status' => $this->status,
            'reviewed_at' => optional($this->reviewed_at)->toDateTimeString(),
        ];
    }
}
