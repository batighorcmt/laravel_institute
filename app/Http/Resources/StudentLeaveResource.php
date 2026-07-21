<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentLeaveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->student;
        $enrollment = $student?->currentEnrollment;

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $student?->full_name ?? $student?->name,
            'roll_no' => $enrollment?->roll_no,
            'class_name' => $enrollment?->class?->bangla_name ?? $enrollment?->class?->name,
            'section_name' => $enrollment?->section?->bangla_name ?? $enrollment?->section?->name,
            'guardian_phone' => $student?->guardian_phone,
            'title' => $this->title,
            'type' => $this->type,
            'reason' => $this->reason,
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'total_days' => $this->start_date && $this->end_date
                ? $this->start_date->diffInDays($this->end_date) + 1
                : null,
            'status' => $this->status,
            'review_note' => $this->review_note,
            'reviewed_by_name' => $this->reviewer?->full_name ?? $this->reviewer?->name,
            'reviewed_at' => optional($this->reviewed_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
