<?php

namespace App\Services;

use App\Models\Notice;
use App\Models\DeviceToken;
use App\Models\User;
use App\Jobs\SendPushNotificationJob;

class PushNotificationService
{
    /**
     * Send notification to appropriate users based on notice audience
     */
    public function sendNoticeNotification(Notice $notice)
    {
        $userIds = collect();

        // 1. Get appropriate user IDs
        if ($notice->audience_type === 'all') {
            $userIds = User::whereHas('schoolRoles', function($q) use ($notice) {
                $q->where('school_id', $notice->school_id);
            })->pluck('id');
        } elseif ($notice->audience_type === 'teachers') {
            $teacherQuery = \App\Models\Teacher::where('school_id', $notice->school_id)->where('status', 'active');
            
            if ($notice->targets()->where('targetable_type', \App\Models\Teacher::class)->exists()) {
                $targetIds = $notice->targets()->where('targetable_type', \App\Models\Teacher::class)->pluck('targetable_id');
                $teacherQuery->whereIn('id', $targetIds);
            }
            
            $userIds = $teacherQuery->whereNotNull('user_id')->pluck('user_id');
        } elseif ($notice->audience_type === 'students') {
            $studentQuery = \App\Models\Student::where('school_id', $notice->school_id)->where('status', 'active');
            
            if ($notice->targets()->exists()) {
                $studentQuery->where(function($q) use ($notice) {
                    $targets = $notice->targets;
                    $studentIds = $targets->where('targetable_type', \App\Models\Student::class)->pluck('targetable_id');
                    if ($studentIds->isNotEmpty()) $q->orWhereIn('id', $studentIds);

                    $classIds = $targets->where('targetable_type', \App\Models\SchoolClass::class)->pluck('targetable_id');
                    if ($classIds->isNotEmpty()) $q->orWhereIn('class_id', $classIds);

                    $sectionIds = $targets->where('targetable_type', \App\Models\Section::class)->pluck('targetable_id');
                    if ($sectionIds->isNotEmpty()) {
                        $q->orWhereHas('currentEnrollment', function($sq) use ($sectionIds) {
                            $sq->whereIn('section_id', $sectionIds);
                        });
                    }
                });
            }
            
            $userIds = $studentQuery->whereNotNull('user_id')->pluck('user_id');
        }

        if ($userIds->isEmpty()) return;

        // 2. Get device tokens with their user IDs
        $tokensData = DeviceToken::whereIn('user_id', $userIds)
            ->whereNotNull('token')
            ->select('token', 'user_id')
            ->get();

        // 3. Dispatch Jobs
        if ($tokensData->isNotEmpty()) {
            $title = $notice->title ?? 'নতুন নোটিশ';
            $body = mb_substr(strip_tags($notice->body ?? ''), 0, 100);
            if (strlen($notice->body ?? '') > 100) $body .= '...';

            foreach ($tokensData->unique('token') as $item) {
                SendPushNotificationJob::dispatch(
                    [$item->token],
                    $title,
                    $body,
                    ['id' => (string)$notice->id, 'type' => 'notice'],
                    $item->user_id,
                    $notice->id
                );
            }
        }
    }
}
