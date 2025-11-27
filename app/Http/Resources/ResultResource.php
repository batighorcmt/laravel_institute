<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'student_id' => $this->student_id,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'total_marks' => $this->total_marks,
            'total_possible_marks' => $this->total_possible_marks,
            'percentage' => $this->percentage,
            'gpa' => $this->gpa,
            'letter_grade' => $this->letter_grade,
            'result_status' => $this->result_status,
            'failed_subjects_count' => $this->failed_subjects_count,
            'absent_subjects_count' => $this->absent_subjects_count,
            'class_position' => $this->class_position,
            'section_position' => $this->section_position,
            'merit_position' => $this->merit_position,
            'is_published' => $this->is_published,
            'published_at' => optional($this->published_at)->toDateTimeString(),
        ];
    }
}
