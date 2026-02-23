<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class RoutineResource extends JsonResource
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
            'day_of_week' => $this->day_of_week,
            'day_name_bn' => match(ucfirst(strtolower($this->day_of_week))) {
                'Saturday' => 'শনিবার',
                'Sunday' => 'রবিবার',
                'Monday' => 'সোমবার',
                'Tuesday' => 'মঙ্গলবার',
                'Wednesday' => 'বুধবার',
                'Thursday' => 'বৃহস্পতিবার',
                'Friday' => 'শুক্রবার',
                default => $this->day_of_week,
            },
            'period_number' => $this->period_number,
            'start_time' => Carbon::parse($this->start_time)->format('h:i A'),
            'end_time' => Carbon::parse($this->end_time)->format('h:i A'),
            'subject' => [
                'id' => $this->subject_id,
                'name' => $this->subject->name ?? 'N/A',
            ],
            'teacher' => [
                'id' => $this->teacher_id,
                'name' => $this->teacher->name ?? 'N/A',
            ],
        ];
    }
}
