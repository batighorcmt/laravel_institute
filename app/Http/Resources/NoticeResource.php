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
            'expiry_at' => optional($this->expiry_at)->toDateTimeString(),
            'status' => $this->status,
            'audience_type' => $this->audience_type,
            'reply_required' => $this->reply_required,
            'attachment_url' => $this->attachment_path ? asset('storage/' . $this->attachment_path) : null,
            'is_read' => $request->user() ? $this->reads()->where('user_id', $request->user()->id)->exists() : false,
            'read_count' => $this->when(auth()->user()?->isPrincipal(), $this->reads()->count()),
            'reply_count' => $this->when(auth()->user()?->isPrincipal(), $this->replies()->count()),
            'targets' => $this->when(auth()->user()?->isPrincipal(), function() {
                return $this->targets->map(function($t) {
                    $typeMap = [
                        \App\Models\Teacher::class => 'Teacher',
                        \App\Models\Student::class => 'Student',
                        \App\Models\SchoolClass::class => 'Class',
                        \App\Models\Section::class => 'Section',
                        \App\Models\Group::class => 'Group',
                    ];
                    return [
                        'id' => $t->targetable_id,
                        'type' => $typeMap[$t->targetable_type] ?? $t->targetable_type
                    ];
                });
            }),
        ];
    }
}
