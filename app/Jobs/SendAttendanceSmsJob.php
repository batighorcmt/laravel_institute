<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;
use App\Models\SmsTemplate;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\SmsLog;
use App\Services\SmsSender;

class SendAttendanceSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    /** @var array<int,array> */
    protected $students;
    /** @var string */
    protected $date;

    /**
     * @param array $students  array keyed by student_id with at least ['status'=>..., 'previous_status'=>..., 'class_id'=>..., 'section_id'=>..., 'school_id'=>...]
     * @param string $date
     */
    public function __construct(array $students, string $date)
    {
        $this->students = $students;
        $this->date = $date;
    }

    public function handle(): void
    {
        $failed = 0;

        // Determine school id from first available entry
        $schoolId = null;
        foreach ($this->students as $sid => $data) { if (!empty($data['school_id'])) { $schoolId = $data['school_id']; break; } }
        $settings = [];
        $genericTemplate = null;
        if ($schoolId) {
            $settings = Setting::forSchool($schoolId)->where(function($q){ $q->where('key','like','sms_%'); })->pluck('value','key');
            $genericTemplate = SmsTemplate::forSchool($schoolId)->where('type','class')->latest()->first();
        }

        foreach ($this->students as $studentId => $data) {
            $newStatus = $data['status'] ?? null;
            if (empty($newStatus)) continue;

            $settingKey = 'sms_class_attendance_' . $newStatus;
            $send = ($settings[$settingKey] ?? '0') === '1';
            if (!$send) { continue; }

            $previous = $data['previous_status'] ?? null;
            if ($previous !== null && $previous === $newStatus) { continue; }

            $student = Student::find($studentId);
            if (!$student) { $failed++; continue; }

            $rawPhone = $student->guardian_phone ?? '';
            $recipientNumber = preg_replace('/[^0-9]/', '', (string)$rawPhone);
            if (empty($recipientNumber) || strlen($recipientNumber) < 10) {
                SmsLog::create([
                    'school_id' => $data['school_id'] ?? $student->school_id ?? null,
                    'sent_by_user_id' => $data['sent_by_user_id'] ?? null,
                    'recipient_type' => 'student',
                    'recipient_category' => 'guardian',
                    'recipient_id' => $student->id,
                    'recipient_name' => $student->student_name_en,
                    'recipient_number' => $recipientNumber ?: null,
                    'message' => '',
                    'status' => 'skipped',
                    'response' => empty($recipientNumber) ? 'no guardian_phone' : 'invalid phone number',
                    'message_type' => 'attendance',
                ]);
                continue;
            }

            $classId = $data['class_id'] ?? null;
            $sectionId = $data['section_id'] ?? null;
            $class = $classId ? SchoolClass::find($classId) : null;
            $section = $sectionId ? Section::find($sectionId) : null;

            // Template lookup
            $template = null;
            if (!empty($schoolId)) {
                $template = SmsTemplate::where(function($q) use ($schoolId){ $q->where('school_id', $schoolId)->orWhereNull('school_id'); })
                    ->whereIn('type', ['general','class'])->where('title', $newStatus)
                    ->orderByRaw("FIELD(type, 'class', 'general')")->first();
            }
            if (!$template) { $template = $genericTemplate; }

            if ($template && !empty($template->content)) {
                $message = str_replace(['{student_name}','{status}','{date}'], [$student->student_name_en, $newStatus, $this->date], $template->content);
            } else {
                $studentName = $student->student_name_bn ?? $student->student_name_en ?? '';
                $className = $class?->name ?? '';
                $sectionName = $section?->name ?? '';
                if ($newStatus === 'present') {
                    $message = "আপনার সন্তানের নাম: {$studentName}. তিনি/তিনি উপস্থিত ছিলেন: {$this->date}. শ্রেণি: {$className} {$sectionName}. - ";
                } elseif ($newStatus === 'late') {
                    $message = "আপনার সন্তানের নাম: {$studentName}. তিনি/তিনি দেরিতে এসেছেন: {$this->date}. শ্রেণি: {$className} {$sectionName}. - ";
                } elseif ($newStatus === 'half_day') {
                    $message = "আপনার সন্তানের নাম: {$studentName}. তিনি/তিনি আধা দিন উপস্থিত ছিলেন: {$this->date}. শ্রেণি: {$className} {$sectionName}. - ";
                } else {
                    $message = "আপনার সন্তানের নাম: {$studentName}. স্ট্যাটাস: {$newStatus} ({$this->date}). শ্রেণি: {$className} {$sectionName}. - ";
                }
            }

            try {
                $result = SmsSender::send($data['school_id'] ?? $schoolId, $recipientNumber, $message);
                $ok = (bool)($result['success'] ?? false);
                $resp = $result['response'] ?? ($result['message'] ?? null);

                SmsLog::create([
                    'school_id' => $data['school_id'] ?? $schoolId,
                    'sent_by_user_id' => $data['sent_by_user_id'] ?? null,
                    'recipient_type' => 'student',
                    'recipient_category' => 'class attendance',
                    'recipient_id' => $student->id,
                    'recipient_name' => $student->student_name_en,
                    'recipient_number' => $recipientNumber,
                    'message' => $message,
                    'status' => $ok ? 'sent' : 'failed',
                    'response' => is_string($resp) ? $resp : json_encode($resp),
                    'message_type' => 'result_notification',
                ]);

                if (!$ok) { $failed++; }
            } catch (\Throwable $e) {
                SmsLog::create([
                    'school_id' => $data['school_id'] ?? $schoolId,
                    'sent_by_user_id' => $data['sent_by_user_id'] ?? null,
                    'recipient_type' => 'student',
                    'recipient_category' => 'class attendance',
                    'recipient_id' => $student->id,
                    'recipient_name' => $student->student_name_en,
                    'recipient_number' => $recipientNumber,
                    'message' => $message ?? '',
                    'status' => 'failed',
                    'response' => 'Exception: ' . $e->getMessage(),
                    'message_type' => 'result_notification',
                ]);
                $failed++;
            }

            // Rate limit
            sleep(1);
        }

        if ($failed > 0) {
            throw new \Exception("SendAttendanceSmsJob completed with {$failed} failed deliveries");
        }
    }
}
