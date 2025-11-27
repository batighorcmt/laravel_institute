<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HomeworkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'homework_date' => optional($this->homework_date)->toDateString(),
            'submission_date' => optional($this->submission_date)->toDateString(),
            'title' => $this->title,
            'description' => $this->description,
            'attachment' => $this->attachment,
        ];
    }
}
