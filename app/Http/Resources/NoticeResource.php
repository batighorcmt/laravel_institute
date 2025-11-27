<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'publish_at' => optional($this->publish_at)->toDateTimeString(),
            'status' => $this->status,
            'school_id' => $this->school_id,
        ];
    }
}
