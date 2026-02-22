<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Setting;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;

class SmsSettingsController extends Controller
{
    protected array $apiKeys = [
        'sms_api_url','sms_api_key','sms_sender_id','sms_masking'
    ];
    protected array $classAttendanceKeys = [
        'sms_class_attendance_present','sms_class_attendance_absent','sms_class_attendance_late','sms_class_attendance_half_day'
    ];
    protected array $extraClassAttendanceKeys = [
        'sms_extra_class_attendance_present','sms_extra_class_attendance_absent','sms_extra_class_attendance_late','sms_extra_class_attendance_half_day'
    ];
    protected array $lessonEvaluationKeys = [
        'sms_lesson_evaluation_completed','sms_lesson_evaluation_partial','sms_lesson_evaluation_not_done','sms_lesson_evaluation_absent'
    ];

    public function index(School $school)
    {
        // Load all sms_* settings for this school
        $settings = Setting::forSchool($school->id)->where(function($q){
            $q->where('key','like','sms_%');
        })->pluck('value','key');

        // Defaults
        $api = [
            'sms_api_url' => $settings['sms_api_url'] ?? '',
            'sms_api_key' => $settings['sms_api_key'] ?? '',
            'sms_sender_id' => $settings['sms_sender_id'] ?? '',
            'sms_masking' => $settings['sms_masking'] ?? '',
        ];
        $classAttendance = [
            'sms_class_attendance_present' => $settings['sms_class_attendance_present'] ?? '1',
            'sms_class_attendance_absent' => $settings['sms_class_attendance_absent'] ?? '1',
            'sms_class_attendance_late' => $settings['sms_class_attendance_late'] ?? '1',
            'sms_class_attendance_half_day' => $settings['sms_class_attendance_half_day'] ?? '1',
        ];
        $extraClassAttendance = [
            'sms_extra_class_attendance_present' => $settings['sms_extra_class_attendance_present'] ?? '0',
            'sms_extra_class_attendance_absent' => $settings['sms_extra_class_attendance_absent'] ?? '1',
            'sms_extra_class_attendance_late' => $settings['sms_extra_class_attendance_late'] ?? '1',
            'sms_extra_class_attendance_half_day' => $settings['sms_extra_class_attendance_half_day'] ?? '0',
        ];
        $lessonEvaluation = [
            'sms_lesson_evaluation_completed' => $settings['sms_lesson_evaluation_completed'] ?? '0',
            'sms_lesson_evaluation_partial' => $settings['sms_lesson_evaluation_partial'] ?? '0',
            'sms_lesson_evaluation_not_done' => $settings['sms_lesson_evaluation_not_done'] ?? '0',
            'sms_lesson_evaluation_absent' => $settings['sms_lesson_evaluation_absent'] ?? '0',
        ];

        $templates = SmsTemplate::forSchool($school->id)->orderByDesc('id')->get();

        return view('principal.institute.settings.sms', [
            'school' => $school,
            'api' => $api,
            'classAttendance' => $classAttendance,
            'extraClassAttendance' => $extraClassAttendance,
            'lessonEvaluation' => $lessonEvaluation,
            'templates' => $templates,
        ]);
    }

    public function saveApi(Request $request, School $school)
    {
        $data = $request->validate([
            'sms_api_url' => 'nullable|string|max:500',
            'sms_api_key' => 'nullable|string|max:500',
            'sms_sender_id' => 'nullable|string|max:100',
            'sms_masking' => 'nullable|string|max:100',
        ]);
        $this->upsertSettings($school->id, $data);
        return back()->with('success','API সেটিংস আপডেট হয়েছে');
    }

    public function saveClassAttendance(Request $request, School $school)
    {
        $payload = [];
        foreach ($this->classAttendanceKeys as $k) {
            $payload[$k] = $request->has($k) ? '1' : '0';
        }
        $this->upsertSettings($school->id, $payload);
        return back()->with('success','ক্লাস হাজিরা SMS সেটিংস আপডেট হয়েছে');
    }

    public function saveExtraClassAttendance(Request $request, School $school)
    {
        $payload = [];
        foreach ($this->extraClassAttendanceKeys as $k) {
            $payload[$k] = $request->has($k) ? '1' : '0';
        }
        $this->upsertSettings($school->id, $payload);
        return back()->with('success','এক্সট্রা ক্লাস হাজিরা SMS সেটিংস আপডেট হয়েছে');
    }

    public function saveLessonEvaluation(Request $request, School $school)
    {
        $payload = [];
        foreach ($this->lessonEvaluationKeys as $k) {
            $payload[$k] = $request->has($k) ? '1' : '0';
        }
        $this->upsertSettings($school->id, $payload);
        return back()->with('success','লেসন ইভেলুশন SMS সেটিংস আপডেট হয়েছে');
    }

    public function storeTemplate(Request $request, School $school)
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'content' => 'required|string',
            'type' => 'required|string|in:general,class,extra_class,lesson_evaluation',
        ]);
        $data['school_id'] = $school->id;

        try {
            SmsTemplate::create($data);
        } catch (\Exception $e) {
            // If it's a truncation error (1265), try to fix the column and retry
            if (str_contains($e->getMessage(), '1265') || str_contains($e->getMessage(), 'Data truncated')) {
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE sms_templates MODIFY COLUMN type ENUM('general', 'class', 'extra_class', 'lesson_evaluation') DEFAULT 'general'");
                    SmsTemplate::create($data); // Retry
                } catch (\Exception $fixEx) {
                    return back()->with('error', 'ডেটাবেস আপডেট করা যায়নি। অনুগ্রহ করে ডেভেলপারকে জানান। (Error: ' . $fixEx->getMessage() . ')');
                }
            } else {
                throw $e;
            }
        }

        return back()->with('success','টেমপ্লেট যুক্ত হয়েছে');
    }

    public function updateTemplate(Request $request, School $school, SmsTemplate $template)
    {
        abort_unless($template->school_id === $school->id, 404);
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'content' => 'required|string',
            'type' => 'required|string|in:general,class,extra_class,lesson_evaluation',
        ]);

        try {
            $template->update($data);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '1265') || str_contains($e->getMessage(), 'Data truncated')) {
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE sms_templates MODIFY COLUMN type ENUM('general', 'class', 'extra_class', 'lesson_evaluation') DEFAULT 'general'");
                    $template->update($data); // Retry
                } catch (\Exception $fixEx) {
                    return back()->with('error', 'ডেটাবেস আপডেট করা যায়নি। (Error: ' . $fixEx->getMessage() . ')');
                }
            } else {
                throw $e;
            }
        }

        return back()->with('success','টেমপ্লেট আপডেট হয়েছে');
    }

    public function destroyTemplate(School $school, SmsTemplate $template)
    {
        abort_unless($template->school_id === $school->id, 404);
        $template->delete();
        return back()->with('success','টেমপ্লেট মুছে ফেলা হয়েছে');
    }

    protected function upsertSettings(int $schoolId, array $data): void
    {
        foreach ($data as $key => $value) {
            Setting::updateOrCreate([
                'school_id' => $schoolId,
                'key' => $key,
            ], [
                'value' => $value,
            ]);
        }
    }
}
