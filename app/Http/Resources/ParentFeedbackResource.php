<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParentFeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->student->student_name_en ?? 'N/A',
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'reply' => $this->reply,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
