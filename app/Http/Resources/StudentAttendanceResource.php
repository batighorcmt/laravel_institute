<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentAttendanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'date' => optional($this->date)->toDateString(),
            'status' => $this->status,
            'remarks' => $this->remarks,
        ];
    }
}
