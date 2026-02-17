<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SmsTemplate;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\SmsLog;
use App\Jobs\SendSmsChunkJob;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class AttendanceSmsService
{
    /**
     * Enqueue attendance SMS payloads in background chunks. Returns report with counts and skipped list.
     *
     * @param \App\Models\School $school
     * @param array $attendanceData
     * @param int $classId
     * @param int $sectionId
     * @param string $date
     * @param bool $isExistingRecord
     * @param array $previousStatuses
     * @param int|null $sentByUserId
     * @return array{sent:int, skipped:array}
     */
    /**
     * @param string $messageContext 'class' or 'extra_class'
     */
    public function enqueueAttendanceSms($school, array $attendanceData, $classId, $sectionId, $date, $isExistingRecord, array $previousStatuses = [], $sentByUserId = null, string $messageContext = 'class')
    {
        $settings = Setting::forSchool($school->id)->where(function($q){ $q->where('key','like','sms_%'); })->pluck('value','key');
        $genericTemplate = SmsTemplate::forSchool($school->id)->where('type', 'class')->latest()->first();

        $payloads = [];
        $skipped = [];

        foreach ($attendanceData as $studentId => $data) {
            $newStatus = $data['status'] ?? null;
            if (empty($newStatus)) continue;

            // Determine previous status. Prefer provided map (captured before DB updates).
            $oldStatus = $previousStatuses[$studentId] ?? null;
            if ($oldStatus === null && $isExistingRecord) {
                // Fall back to DB lookup when caller didn't supply previous statuses
                $oldStatus = Attendance::where('student_id', $studentId)
                    ->where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('date', $date)
                    ->value('status');
            }

            // If the status didn't change, skip.
            if ($oldStatus === $newStatus) continue;

            // Settings key differs for class vs extra_class
            $settingPrefix = $messageContext === 'extra_class' ? 'sms_extra_class_attendance_' : 'sms_class_attendance_';
            $settingKey = $settingPrefix . $newStatus;
            $send = ($settings[$settingKey] ?? '0') === '1';

            // For updates (we have an old status and it changed), always send regardless of setting.
            $shouldSend = $send;
            if ($oldStatus !== null && $oldStatus !== $newStatus) {
                $shouldSend = true;
            }
            if (!$shouldSend) { continue; }

            $student = Student::find($studentId);
            if (!$student) {
                $skipped[] = ['student_id'=>$studentId,'reason'=>'student_not_found'];
                continue;
            }

            $rawPhone = $student->guardian_phone ?? '';
            $recipientNumber = preg_replace('/[^0-9]/', '', (string)$rawPhone);
            if (empty($recipientNumber)) {
                // persist skipped log
                SmsLog::create([
                    'school_id' => $school->id,
                    'sent_by_user_id' => $sentByUserId ?? Auth::id(),
                    'recipient_type' => 'student',
                    'recipient_category' => 'guardian',
                    'recipient_id' => $student->id,
                    'recipient_name' => $student->student_name_en,
                    'recipient_number' => null,
                    'message' => '',
                    'status' => 'skipped',
                    'response' => 'no guardian_phone',
                    'message_type' => 'attendance',
                ]);
                $skipped[] = ['student_id'=>$studentId,'reason'=>'no_guardian_phone'];
                continue;
            }
            if (strlen($recipientNumber) < 10) {
                SmsLog::create([
                    'school_id' => $school->id,
                    'sent_by_user_id' => $sentByUserId ?? Auth::id(),
                    'recipient_type' => 'student',
                    'recipient_category' => 'guardian',
                    'recipient_id' => $student->id,
                    'recipient_name' => $student->student_name_en,
                    'recipient_number' => $recipientNumber,
                    'message' => '',
                    'status' => 'skipped',
                    'response' => 'invalid phone number',
                    'message_type' => 'attendance',
                ]);
                $skipped[] = ['student_id'=>$studentId,'reason'=>'invalid_phone'];
                continue;
            }

            $class = SchoolClass::find($classId);
            $section = Section::find($sectionId);

            // Choose template per-status or fallback. Prefer context-specific templates (extra_class/class)
            $templateTypes = ['general', $messageContext];
            $template = SmsTemplate::where(function($q) use ($school) {
                $q->where('school_id', $school->id)->orWhereNull('school_id');
            })->whereIn('type',$templateTypes)->where('title', $newStatus)
                ->orderByRaw("FIELD(type, '{$messageContext}', 'general')")->first();
            if (!$template) { $template = $genericTemplate; }

            if ($template && !empty($template->content)) {
                $message = str_replace(
                    ['{student_name}', '{status}', '{date}'],
                    [$student->student_name_en, $newStatus, $date],
                    $template->content
                );
            } else {
                $studentName = $student->student_name_bn ?? $student->student_name_en ?? '';
                $className = $class?->name ?? '';
                $sectionName = $section?->name ?? '';
                if ($newStatus === 'present') {
                    $message = "আপনার সন্তানের নাম: {$studentName}. তিনি/তিনি উপস্থিত ছিলেন: {$date}. শ্রেণি: {$className} {$sectionName}. - {$school->name}";
                } elseif ($newStatus === 'late') {
                    $message = "আপনার সন্তানের নাম: {$studentName}. তিনি/তিনি দেরিতে এসেছেন: {$date}. শ্রেণি: {$className} {$sectionName}. - {$school->name}";
                } elseif ($newStatus === 'half_day') {
                    $message = "আপনার সন্তানের নাম: {$studentName}. তিনি/তিনি আধা দিন উপস্থিত ছিলেন: {$date}. শ্রেণি: {$className} {$sectionName}. - {$school->name}";
                } else {
                    $message = "আপনার সন্তানের নাম: {$studentName}. স্ট্যাটাস: {$newStatus} ({$date}). শ্রেণি: {$className} {$sectionName}. - {$school->name}";
                }
            }

            $enrollment = StudentEnrollment::where('student_id', $studentId)->where('class_id', $classId)->where('section_id', $sectionId)->where('status', 'active')->first();
            $meta = [
                'recipient_type' => 'student',
                'recipient_category' => $messageContext === 'extra_class' ? 'extra class attendance' : 'class attendance',
                'recipient_id' => $student->id,
                'recipient_name' => $student->student_name_en,
                'roll_number' => $enrollment ? $enrollment->roll_no : null,
                'class_name' => $class?->name ?? null,
                'section_name' => $section?->name ?? null,
                'message_type' => 'attendance',
            ];

            $payloads[] = ['mobile' => $recipientNumber, 'message' => $message, 'meta' => $meta];
        }

        // Chunk and dispatch jobs. If `SMS_SEND_IMMEDIATELY` is truthy, dispatch synchronously
        $chunkSize = (int) env('SMS_CHUNK_SIZE', 20);
        $chunks = array_chunk($payloads, max(1, $chunkSize));
        $sendImmediately = (bool) env('SMS_SEND_IMMEDIATELY', false);
        foreach ($chunks as $chunk) {
                if ($sendImmediately) {
                // Dispatch synchronously so sending happens inline (useful when queue workers aren't running)
                if (method_exists(SendSmsChunkJob::class, 'dispatchSync')) {
                    SendSmsChunkJob::dispatchSync($school->id, $sentByUserId, $chunk);
                } else {
                    // Fallback: instantiate and run handle() directly
                    $job = new SendSmsChunkJob($school->id, $sentByUserId, $chunk);
                    $job->handle();
                }
            } else {
                // Attach context meta to chunk? the SendSmsChunkJob currently accepts raw payloads; context is embedded in message body/template.
                SendSmsChunkJob::dispatch($school->id, $sentByUserId, $chunk);
            }
        }

        return ['sent' => count($payloads), 'skipped' => $skipped];
    }
}
