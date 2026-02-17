<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SmsTemplate;
use App\Models\Student;
use App\Models\LessonEvaluation;
use App\Models\LessonEvaluationRecord;
use App\Models\SmsLog;
use App\Jobs\SendSmsChunkJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class LessonEvaluationSmsService
{
    /**
     * Send SMS notifications for a lesson evaluation records.
     *
     * @param LessonEvaluation $evaluation
     * @param array $records Array of LessonEvaluationRecord models
     * @param int|null $sentByUserId
     * @param array $previousStatuses Map of student_id => previous_status
     * @return array{sent:int, skipped:array}
     */
    public function sendEvaluationSms(LessonEvaluation $evaluation, $records, $sentByUserId = null, array $previousStatuses = [])
    {
        $school = $evaluation->school;
        if (!$school) return ['sent' => 0, 'skipped' => []];

        // Fetch settings for lesson evaluation
        $settings = Setting::forSchool($school->id)
            ->where('key', 'like', 'sms_lesson_evaluation_%')
            ->pluck('value', 'key');

        $payloads = [];
        $skipped = [];

        foreach ($records as $record) {
            $status = $record->status;
            
            // IF update: only send if status changed
            if (isset($previousStatuses[$record->student_id])) {
                if ($previousStatuses[$record->student_id] === $status) {
                    continue; // Skip if status unchanged during update
                }
            }

            // Check if SMS is enabled for this status
            $settingKey = 'sms_lesson_evaluation_' . $status;
            $isEnabled = ($settings[$settingKey] ?? '0') === '1';

            if (!$isEnabled) {
                continue;
            }

            $student = $record->student;
            if (!$student) {
                $skipped[] = ['student_id' => $record->student_id, 'reason' => 'student_not_found'];
                continue;
            }

            $rawPhone = $student->guardian_phone ?? '';
            $recipientNumber = preg_replace('/[^0-9]/', '', (string)$rawPhone);
            
            if (empty($recipientNumber) || strlen($recipientNumber) < 10) {
                // Log skipped
                SmsLog::create([
                    'school_id' => $school->id,
                    'sent_by_user_id' => $sentByUserId ?? Auth::id(),
                    'recipient_type' => 'student',
                    'recipient_category' => 'guardian',
                    'recipient_id' => $student->id,
                    'recipient_name' => $student->full_name,
                    'recipient_number' => $recipientNumber ?: null,
                    'message' => '',
                    'status' => 'skipped',
                    'response' => empty($recipientNumber) ? 'no guardian_phone' : 'invalid phone number',
                    'message_type' => 'lesson_evaluation',
                ]);
                $skipped[] = ['student_id' => $student->id, 'reason' => empty($recipientNumber) ? 'no_phone' : 'invalid_phone'];
                continue;
            }

            // Fetch template for this specific status
            $template = SmsTemplate::forSchool($school->id)
                ->whereIn('type', ['lesson_evaluation', 'general'])
                ->where('title', $status)
                ->orderByRaw("FIELD(type, 'lesson_evaluation', 'general')")
                ->latest()
                ->first();

            // Prepare Message
            $statusLabels = [
                'completed' => 'পড়া হয়েছে',
                'partial' => 'আংশিক হয়েছে',
                'not_done' => 'হয় নাই',
                'absent' => 'অনুপস্থিত',
            ];
            $statusLabel = $statusLabels[$status] ?? $status;

            if ($template && !empty($template->content)) {
                $message = str_replace(
                    ['{student_name}', '{status}', '{subject}', '{date}'],
                    [$student->full_name, $statusLabel, $evaluation->subject?->name, $evaluation->evaluation_date],
                    $template->content
                );
            } else {
                // Default message
                $subjectName = $evaluation->subject?->name;
                $date = $evaluation->evaluation_date;
                $studentName = $student->full_name_bn ?? $student->full_name;
                
                $message = "প্রিয় অভিভাবক, আজ ({$date}) '{$subjectName}' বিষয়ে আপনার সন্তান {$studentName}-এর লেসন ইভ্যালুয়েশন রিপোর্ট: {$statusLabel}। - {$school->name}";
            }

            $payloads[] = [
                'mobile' => $recipientNumber,
                'message' => $message,
                'meta' => [
                    'recipient_type' => 'student',
                    'recipient_category' => 'lesson evaluation',
                    'recipient_id' => $student->id,
                    'recipient_name' => $student->full_name,
                    'roll_number' => $student->roll,
                    'class_name' => $evaluation->class?->name,
                    'section_name' => $evaluation->section?->name,
                    'subject' => $evaluation->subject?->name,
                    'date' => $evaluation->evaluation_date,
                    'message_type' => 'lesson_evaluation',
                ]
            ];
        }

        if (empty($payloads)) {
            return ['sent' => 0, 'skipped' => $skipped];
        }

        // Chunk and dispatch jobs
        $chunkSize = (int) env('SMS_CHUNK_SIZE', 20);
        $chunks = array_chunk($payloads, max(1, $chunkSize));
        $sendImmediately = (bool) env('SMS_SEND_IMMEDIATELY', false);

        foreach ($chunks as $chunk) {
            if ($sendImmediately) {
                if (method_exists(SendSmsChunkJob::class, 'dispatchSync')) {
                    SendSmsChunkJob::dispatchSync($school->id, $sentByUserId, $chunk);
                } else {
                    $job = new SendSmsChunkJob($school->id, $sentByUserId, $chunk);
                    $job->handle();
                }
            } else {
                SendSmsChunkJob::dispatch($school->id, $sentByUserId, $chunk);
            }
        }

        return ['sent' => count($payloads), 'skipped' => $skipped];
    }
}
