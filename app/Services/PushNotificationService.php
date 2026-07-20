<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\DeviceToken;
use App\Models\Notice;
use App\Models\User;

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
            $userIds = User::whereHas('schoolRoles', function ($q) use ($notice) {
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
                $studentQuery->where(function ($q) use ($notice) {
                    $targets = $notice->targets;
                    $studentIds = $targets->where('targetable_type', \App\Models\Student::class)->pluck('targetable_id');
                    if ($studentIds->isNotEmpty()) {
                        $q->orWhereIn('id', $studentIds);
                    }

                    $classIds = $targets->where('targetable_type', \App\Models\SchoolClass::class)->pluck('targetable_id');
                    if ($classIds->isNotEmpty()) {
                        $q->orWhereIn('class_id', $classIds);
                    }

                    $sectionIds = $targets->where('targetable_type', \App\Models\Section::class)->pluck('targetable_id');
                    if ($sectionIds->isNotEmpty()) {
                        $q->orWhereHas('currentEnrollment', function ($sq) use ($sectionIds) {
                            $sq->whereIn('section_id', $sectionIds);
                        });
                    }
                });
            }

            $userIds = $studentQuery->whereNotNull('user_id')->pluck('user_id');
        }

        if ($userIds->isEmpty()) {
            return;
        }

        // 2. Get device tokens with their user IDs
        $tokensData = DeviceToken::whereIn('user_id', $userIds)
            ->whereNotNull('token')
            ->select('token', 'user_id')
            ->get();

        // 3. Dispatch Jobs
        if ($tokensData->isNotEmpty()) {
            $title = $notice->title ?? 'নতুন নোটিশ';
            $body = mb_substr(strip_tags($notice->body ?? ''), 0, 100);
            if (strlen($notice->body ?? '') > 100) {
                $body .= '...';
            }

            foreach ($tokensData->unique('token') as $item) {
                SendPushNotificationJob::dispatch(
                    [$item->token],
                    $title,
                    $body,
                    ['id' => (string) $notice->id, 'type' => 'notice'],
                    $item->user_id,
                    $notice->id
                );
            }
        }
    }

    /**
     * Send push notification when a teacher is assigned invigilation duty.
     *
     * @param  int  $teacherUserId  The user_id of the assigned teacher
     * @param  string  $dutyDate  Date string (Y-m-d)
     * @param  string  $roomNo  Room number/label
     * @param  string|null  $shift  Shift name (e.g. "সকাল", "দুপুর")
     * @param  int|null  $invigilationId  ID of the ExamRoomInvigilation record (for deep-link)
     */
    public function sendInvigilationDutyNotification(
        int $teacherUserId,
        string $dutyDate,
        string $roomNo,
        ?string $shift = null,
        ?int $invigilationId = null
    ): void {
        $tokens = DeviceToken::where('user_id', $teacherUserId)
            ->whereNotNull('token')
            ->pluck('token')
            ->unique();

        if ($tokens->isEmpty()) {
            return;
        }

        // Format date: Y-m-d -> d/m/Y  (Bangla style)
        $dateFormatted = \Carbon\Carbon::parse($dutyDate)->format('d/m/Y');

        // Build shift label in Bangla
        $shiftBn = '';
        if ($shift) {
            $shiftMap = [
                'morning' => 'সকাল',
                'afternoon' => 'বিকেল',
                'evening' => 'বিকেল',
                'সকাল' => 'সকাল',
                'দুপুর' => 'দুপুর',
                'বিকেল' => 'বিকেল',
            ];
            $shiftBn = $shiftMap[strtolower(trim($shift))] ?? $shift;
        }

        $shiftPart = $shiftBn ? "{$shiftBn} শিফটে " : '';

        $title = 'কক্ষ পরিদর্শকের দায়িত্ব';
        $body = "আপনাকে {$dateFormatted} তারিখ {$shiftPart}{$roomNo} নম্বর কক্ষে পরিদর্শকের দায়িত্ব দেওয়া হয়েছে";

        $data = [
            'type' => 'invigilation_duty',
            'duty_date' => $dutyDate,
            'room_no' => $roomNo,
        ];
        if ($invigilationId) {
            $data['invigilation_id'] = (string) $invigilationId;
        }

        foreach ($tokens as $token) {
            SendPushNotificationJob::dispatch(
                [$token],
                $title,
                $body,
                $data,
                $teacherUserId
            );
        }
    }

    /**
     * Send push notification to every enrolled student of a class/section
     * announcing a new (or updated) homework entry.
     *
     * @param  \App\Models\Homework  $homework
     * @param  \Illuminate\Support\Collection<int>  $studentIds  Student IDs (not user IDs) to notify
     * @param  bool  $isUpdate  Whether this is an update to existing homework (changes wording only)
     */
    public function sendHomeworkNotification($homework, $studentIds, bool $isUpdate = false): void
    {
        if ($studentIds->isEmpty()) {
            return;
        }

        $userIds = \App\Models\Student::whereIn('id', $studentIds)
            ->whereNotNull('user_id')
            ->pluck('user_id', 'id');

        if ($userIds->isEmpty()) {
            return;
        }

        $tokensData = DeviceToken::whereIn('user_id', $userIds->values())
            ->whereNotNull('token')
            ->select('token', 'user_id')
            ->get();

        if ($tokensData->isEmpty()) {
            return;
        }

        $subjectName = $homework->subject?->name ?? '';
        $title = $isUpdate ? 'হোমওয়ার্ক আপডেট হয়েছে' : 'নতুন হোমওয়ার্ক দেওয়া হয়েছে';
        $body = trim($subjectName.': '.mb_substr($homework->description ?? '', 0, 100));
        if (mb_strlen($homework->description ?? '') > 100) {
            $body .= '...';
        }

        foreach ($tokensData->unique('token') as $item) {
            SendPushNotificationJob::dispatch(
                [$item->token],
                $title,
                $body,
                ['id' => (string) $homework->id, 'type' => 'homework'],
                $item->user_id
            );
        }
    }

    /**
     * Send push notification for attendance status update
     */
    public function sendAttendanceNotification($studentId, $status, $date, $type = 'class', ?string $titleOverride = null, ?string $bodyOverride = null)
    {
        $student = \App\Models\Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->find($studentId);
        if (! $student || ! $student->user_id) {
            return;
        }

        $userId = $student->user_id;
        $tokens = DeviceToken::where('user_id', $userId)->whereNotNull('token')->pluck('token')->unique();

        if ($tokens->isEmpty()) {
            return;
        }

        if ($titleOverride !== null && $bodyOverride !== null) {
            // Caller (e.g. biometric entry/exit job) already built a specific,
            // punch-time-aware message — use it as-is instead of the generic one below.
            $title = $titleOverride;
            $body = $bodyOverride;
        } else {
            $statusBn = [
                'present' => 'উপস্থিত',
                'absent' => 'অনুপস্থিত',
                'late' => 'বিলম্বিত',
                'excused' => 'ছুটি',
            ];

            $statusText = $statusBn[$status] ?? $status;
            $dateStr = \Carbon\Carbon::parse($date)->format('d-m-Y');

            // Source class/section from the student's active enrollment —
            // the same source of truth every report uses — instead of the
            // stale, often-unset students.class_id column, which left this
            // blank for students whose homeroom class was never backfilled.
            $enrollment = $student->currentEnrollment;
            $classPart = $enrollment?->class
                ? ($enrollment->class->bangla_name ?: $enrollment->class->name)
                : '';
            $sectionPart = $enrollment?->section
                ? ($enrollment->section->bangla_name ?: $enrollment->section->name)
                : '';
            $className = trim("$classPart $sectionPart");

            $title = 'হাজিরা নোটিফিকেশন';

            if ($type === 'extra_class') {
                $body = "এক্সট্রা ক্লাস হাজিরা: {$student->full_name} ($className) আজকের ক্লাসে \"$statusText\"। তারিখ: $dateStr";
            } else {
                $body = "ক্লাস হাজিরা: {$student->full_name} ($className) আজকের ক্লাসে \"$statusText\"। তারিখ: $dateStr";
            }
        }

        SendPushNotificationJob::dispatch(
            $tokens->toArray(),
            $title,
            $body,
            ['student_id' => (string) $studentId, 'type' => 'attendance'],
            $userId
        );
    }
}
