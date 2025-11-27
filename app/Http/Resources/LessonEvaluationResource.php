<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonEvaluationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'teacher_id' => $this->teacher_id,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'subject_id' => $this->subject_id,
            'evaluation_date' => optional($this->evaluation_date)->toDateString(),
            'evaluation_time' => $this->evaluation_time,
            'notes' => $this->notes,
            'status' => $this->status,
            'stats' => method_exists($this->resource,'getCompletionStats') ? $this->getCompletionStats() : null,
        ];
    }
}
