<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserSchoolRole;
use App\Services\SmsSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SmsController extends Controller
{
    public function panel(School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get(['id','name','numeric_value']);
        $sections = Section::forSchool($school->id)->orderBy('name')->get(['id','name','class_id']);
        $templates = SmsTemplate::forSchool($school->id)->orderByDesc('id')->get(['id','title','content']);

        // Teachers for select - join with teachers table to get phone
        $teacherUserIds = UserSchoolRole::where('school_id',$school->id)
            ->whereHas('role', fn($q)=>$q->where('name', Role::TEACHER))
            ->pluck('user_id');
        
        $teachers = User::whereIn('users.id', $teacherUserIds)
            ->leftJoin('teachers', 'teachers.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('users.id', 'users.name', 'teachers.phone')
            ->get();

        // SMS Balance & Capacity (cached best-effort)
        $apiKey = Setting::forSchool($school->id)->where('key','sms_api_key')->value('value');
        $perSmsSetting = Setting::forSchool($school->id)->where('key','sms_per_sms_cost')->value('value');
        $perSmsCost = $perSmsSetting && is_numeric($perSmsSetting) ? (float)$perSmsSetting : 0.35; // fallback

        $cacheKey = 'sms_balance_school_'.$school->id;
        $balanceData = Cache::remember($cacheKey, now()->addMinutes(3), function() use ($apiKey) {
            $out = [
                'balance' => null,
                'raw' => null,
                'error' => null,
                'fetched_at' => now(),
            ];
            if (!$apiKey) { $out['error'] = 'API key configured নয়'; return $out; }
            try {
                $url = 'http://bulksmsbd.net/api/getBalanceApi?api_key='.urlencode($apiKey);
                $resp = Http::timeout(12)->get($url);
                if (!$resp->successful()) {
                    $out['error'] = 'HTTP '.$resp->status();
                    $out['raw'] = $resp->body();
                    return $out;
                }
                $body = $resp->body();
                $out['raw'] = $body;
                $json = @json_decode($body, true);
                $balance = null;
                if (is_array($json)) {
                    $balance = $json['balance'] ?? ($json['Balance'] ?? ($json['data']['balance'] ?? ($json['sms'] ?? null)));
                }
                if ($balance === null) {
                    if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', (string)$body, $m)) { $balance = $m[1]; }
                }
                if ($balance !== null && is_numeric($balance)) {
                    $out['balance'] = (float)$balance;
                } else {
                    $out['error'] = $out['error'] ?: 'Unexpected response';
                }
            } catch (\Throwable $e) {
                $out['error'] = $e->getMessage();
            }
            return $out;
        });

        $smsBalance = $balanceData['balance'];
        $smsBalanceError = $balanceData['error'];
        $smsBalanceRaw = $balanceData['raw'];
        $smsBalanceFetchedAt = $balanceData['fetched_at'];
        $smsPossible = ($smsBalance !== null && $perSmsCost > 0) ? (int)floor($smsBalance / $perSmsCost) : null;

        return view('principal.sms.panel', compact(
            'school','classes','sections','templates','teachers',
            'smsBalance','smsBalanceError','smsBalanceRaw','smsBalanceFetchedAt','smsPossible','perSmsCost'
        ));
    }

    public function send(Request $request, School $school)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
            'recipients_json' => 'nullable|string',
            'target' => 'nullable|string',
            'submission_uid' => 'nullable|string|max:100',
            // fallbacks
            'numbers' => 'nullable|string',
            'teacher_id' => 'nullable|integer',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'integer',
            'student_id' => 'nullable|integer',
            'class_id' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer',
        ]);

        $message = trim($data['message']);

        // Idempotency: prevent duplicate submits within short window
        if (!empty($data['submission_uid'])) {
            $key = 'sms_submit_'.$school->id.'_'.$data['submission_uid'];
            if (!Cache::add($key, 1, now()->addMinutes(2))) {
                return back()->with('error','এই অনুরোধটি ইতোমধ্যে প্রক্রিয়া হয়েছে (ডুপ্লিকেট সাবমিশন)।');
            }
        }
        $recipients = [];

        // 1) Prefer aggregated recipients JSON
        if (!empty($data['recipients_json'])) {
            $arr = json_decode($data['recipients_json'], true);
            if (is_array($arr)) {
                foreach ($arr as $r) {
                    $num = preg_replace('/[^0-9]/','', (string)($r['number'] ?? ''));
                    // normalize BD format: 8801XXXXXXXXX -> 01XXXXXXXXX
                    if (strlen($num) === 13 && str_starts_with($num,'880')) { $num = '0'.substr($num,3); }
                    if (!$num) continue;
                    $recipients[$num] = [
                        'recipient_type' => $r['category'] ?? 'custom',
                        'recipient_category' => $data['target'] ?? 'custom_numbers',
                        'recipient_id' => $r['id'] ?? null,
                        'recipient_name' => $r['name'] ?? null,
                        'recipient_role' => $r['role'] ?? null,
                        'roll_number' => $r['roll'] ?? null,
                        'class_name' => $r['class_name'] ?? null,
                        'section_name' => $r['section_name'] ?? null,
                    ];
                }
            }
        }

        // 2) Fallbacks when no aggregated
        if (empty($recipients)) {
            $target = $data['target'] ?? '';
            if ($target === 'custom_numbers' && !empty($data['numbers'])) {
                $parts = preg_split('/[\s,;]+/', $data['numbers']);
                foreach ($parts as $n) {
                    $n = preg_replace('/[^0-9]/','', (string)$n);
                    if ($n) { $recipients[$n] = ['recipient_type'=>'custom','recipient_category'=>'custom_numbers']; }
                }
            }
            if ($target === 'teacher_one' && !empty($data['teacher_id'])) {
                $t = User::leftJoin('teachers', 'teachers.user_id', '=', 'users.id')
                    ->where('users.id', (int)$data['teacher_id'])
                    ->select('users.id', 'users.name', 'teachers.phone')
                    ->first();
                if ($t && $t->phone) {
                    $num = preg_replace('/[^0-9]/','', (string)$t->phone);
                    if (strlen($num) === 13 && str_starts_with($num,'880')) { $num = '0'.substr($num,3); }
                    if ($num) {
                        $recipients[$num] = [ 'recipient_type'=>'teacher','recipient_category'=>'teacher_one','recipient_id'=>$t->id,'recipient_name'=>$t->name,'recipient_role'=>'teacher' ];
                    }
                }
            }
            if ($target === 'teachers_selected' && !empty($data['teacher_ids'])) {
                $ts = User::leftJoin('teachers', 'teachers.user_id', '=', 'users.id')
                    ->whereIn('users.id', $data['teacher_ids'])
                    ->select('users.id', 'users.name', 'teachers.phone')
                    ->get();
                foreach ($ts as $t) {
                    if ($t->phone) {
                        $num = preg_replace('/[^0-9]/','', (string)$t->phone);
                        if (strlen($num) === 13 && str_starts_with($num,'880')) { $num = '0'.substr($num,3); }
                        if ($num) {
                            $recipients[$num] = [ 'recipient_type'=>'teacher','recipient_category'=>'teachers_selected','recipient_id'=>$t->id,'recipient_name'=>$t->name,'recipient_role'=>'teacher' ];
                        }
                    }
                }
            }
            if ($target === 'teacher_all') {
                $teacherUserIds = UserSchoolRole::where('school_id',$school->id)
                    ->whereHas('role', fn($q)=>$q->where('name', Role::TEACHER))
                    ->pluck('user_id');
                $ts = User::leftJoin('teachers', 'teachers.user_id', '=', 'users.id')
                    ->whereIn('users.id', $teacherUserIds)
                    ->select('users.id', 'users.name', 'teachers.phone')
                    ->get();
                foreach ($ts as $t) {
                    if ($t->phone) {
                        $num = preg_replace('/[^0-9]/','', (string)$t->phone);
                        if (strlen($num) === 13 && str_starts_with($num,'880')) { $num = '0'.substr($num,3); }
                        if ($num) {
                            $recipients[$num] = [ 'recipient_type'=>'teacher','recipient_category'=>'teacher_all','recipient_id'=>$t->id,'recipient_name'=>$t->name,'recipient_role'=>'teacher' ];
                        }
                    }
                }
            }

            // Students
            $currentYear = AcademicYear::forSchool($school->id)->current()->first();
            $yearVal = $currentYear?->id;
            if ($target === 'student_one' && !empty($data['student_id'])) {
                $row = StudentEnrollment::select('student_enrollments.*','students.student_name_bn','students.student_name_en','students.guardian_phone','classes.name as class_name','sections.name as section_name')
                    ->join('students','students.id','=','student_enrollments.student_id')
                    ->join('classes','classes.id','=','student_enrollments.class_id')
                    ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
                    ->where('student_enrollments.school_id',$school->id)
                    ->where('student_enrollments.student_id',(int)$data['student_id'])
                    ->where('student_enrollments.academic_year_id',$yearVal)
                    ->first();
                if ($row && $row->guardian_phone) {
                    $num = preg_replace('/[^0-9]/','', (string)$row->guardian_phone);
                    if (strlen($num) === 13 && str_starts_with($num,'880')) { $num = '0'.substr($num,3); }
                    if ($num) {
                        $name = $row->student_name_bn ?: $row->student_name_en;
                        $recipients[$num] = [ 'recipient_type'=>'student','recipient_category'=>'student_one','recipient_id'=>$row->student_id,'recipient_name'=>$name,'recipient_role'=>'student','roll_number'=>$row->roll_no,'class_name'=>$row->class_name,'section_name'=>$row->section_name ];
                    }
                }
            }
            if ($target === 'students_all' && !empty($data['class_id'])) {
                $q = StudentEnrollment::select('student_enrollments.*','students.student_name_bn','students.student_name_en','students.guardian_phone','classes.name as class_name','sections.name as section_name')
                    ->join('students','students.id','=','student_enrollments.student_id')
                    ->join('classes','classes.id','=','student_enrollments.class_id')
                    ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
                    ->where('student_enrollments.school_id',$school->id)
                    ->where('student_enrollments.academic_year_id',$yearVal)
                    ->where('student_enrollments.class_id',(int)$data['class_id']);
                if (!empty($data['section_id'])) { $q->where('student_enrollments.section_id',(int)$data['section_id']); }
                foreach ($q->get() as $row) {
                    if ($row->guardian_phone) {
                        $num = preg_replace('/[^0-9]/','', (string)$row->guardian_phone);
                        if (strlen($num) === 13 && str_starts_with($num,'880')) { $num = '0'.substr($num,3); }
                        if ($num) {
                            $name = $row->student_name_bn ?: $row->student_name_en;
                            $recipients[$num] = [ 'recipient_type'=>'student','recipient_category'=>'students_all','recipient_id'=>$row->student_id,'recipient_name'=>$name,'recipient_role'=>'student','roll_number'=>$row->roll_no,'class_name'=>$row->class_name,'section_name'=>$row->section_name ];
                        }
                    }
                }
            }
            if ($target === 'students_selected' && !empty($data['student_ids'])) {
                $rows = StudentEnrollment::select('student_enrollments.*','students.student_name_bn','students.student_name_en','students.guardian_phone','classes.name as class_name','sections.name as section_name')
                    ->join('students','students.id','=','student_enrollments.student_id')
                    ->join('classes','classes.id','=','student_enrollments.class_id')
                    ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
                    ->where('student_enrollments.school_id',$school->id)
                    ->where('student_enrollments.academic_year_id',$yearVal)
                    ->whereIn('student_enrollments.student_id',$data['student_ids'])
                    ->get();
                foreach ($rows as $row) {
                    if ($row->guardian_phone) {
                        $num = preg_replace('/[^0-9]/','', (string)$row->guardian_phone);
                        if (strlen($num) === 13 && str_starts_with($num,'880')) { $num = '0'.substr($num,3); }
                        if ($num) {
                            $name = $row->student_name_bn ?: $row->student_name_en;
                            $recipients[$num] = [ 'recipient_type'=>'student','recipient_category'=>'students_selected','recipient_id'=>$row->student_id,'recipient_name'=>$name,'recipient_role'=>'student','roll_number'=>$row->roll_no,'class_name'=>$row->class_name,'section_name'=>$row->section_name ];
                        }
                    }
                }
            }
        }

        $recipients = collect($recipients)->take(1000); // cap
        if ($recipients->isEmpty()) {
            return back()->with('error','কোনো প্রাপক পাওয়া যায়নি।')->withInput();
        }

        $payloads = [];
        foreach ($recipients as $to => $meta) {
            $payloads[] = [
                'mobile' => $to,
                'message' => $message,
                'meta' => $meta
            ];
        }

        if (empty($payloads)) {
            return back()->with('error','কোনো বৈধ নম্বর পাওয়া যায়নি।')->withInput();
        }

        // Chunk and dispatch jobs
        $chunkSize = (int) env('SMS_CHUNK_SIZE', 50);
        $batchDelaySec = (int) env('SMS_BATCH_DELAY_SEC', 5); // spacing to avoid DB/API spikes
        $chunks = array_chunk($payloads, max(1, $chunkSize));
        $userId = auth()->id();

        foreach ($chunks as $idx => $chunk) {
            \App\Jobs\SendSmsChunkJob::dispatch($school->id, $userId, $chunk)
                ->delay(now()->addSeconds($idx * $batchDelaySec));
        }

        return back()->with('success', 'মোট '.$recipients->count().' টি নম্বরে পাঠানোর জন্য '.count($chunks).'টি ব্যাচে (প্রতি ব্যাচ ~'.$chunkSize.' বার্তা) কিউ করা হয়েছে। কিছুক্ষণ পর লগ চেক করুন।');
    }

    public function logs(Request $request, School $school)
    {
        $q = SmsLog::forSchool($school->id)->orderByDesc('created_at');
        if ($v = $request->get('number')) { $q->where('recipient_number','like','%'.$v.'%'); }
        if ($v = $request->get('status')) { $q->where('status',$v); }
        if ($v = $request->get('type')) { $q->where('recipient_type',$v); }
        if ($v = $request->get('date_from')) { $q->where('created_at','>=',$v.' 00:00:00'); }
        if ($v = $request->get('date_to')) { $q->where('created_at','<=',$v.' 23:59:59'); }
        $totalLogsCount = $q->count();
        $totalParts = $q->clone()->reorder()->selectRaw("
            SUM(
                IF(LENGTH(message) != CHAR_LENGTH(message),
                    IF(CHAR_LENGTH(message) <= 70, 1, CEIL(CHAR_LENGTH(message) / 67)),
                    IF(CHAR_LENGTH(message) <= 160, 1, CEIL(CHAR_LENGTH(message) / 153))
                )
            ) as total_parts
        ")->value('total_parts') ?: 0;

        $successCount = $q->clone()->whereIn('status', ['success', 'sent'])->count();

        $logs = $q->paginate(50)->withQueryString();
        return view('principal.sms.logs', compact('school','logs', 'totalParts', 'totalLogsCount', 'successCount'));
    }

    public function view(School $school, SmsLog $log)
    {
        abort_unless($log->school_id === $school->id, 404);
        return view('principal.sms.log_view', compact('school','log'));
    }
}
