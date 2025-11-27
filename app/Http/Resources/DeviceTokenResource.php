<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeviceTokenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'last_used_at' => optional($this->last_used_at)->toDateTimeString(),
        ];
    }
}
