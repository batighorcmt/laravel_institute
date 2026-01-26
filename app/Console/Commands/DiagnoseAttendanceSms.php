<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\School;
use App\Services\AttendanceSmsService;

class DiagnoseAttendanceSms extends Command
{
    protected $signature = 'diagnose:attendance-sms';
    protected $description = 'Diagnose latest attendance SMS enqueue behavior';

    public function handle()
    {
        $att = Attendance::orderByDesc('created_at')->first();
        if (! $att) {
            $this->error('No attendance records found');
            return 1;
        }

        $this->info('Found attendance: school='.$att->school_id.' class='.$att->class_id.' section='.$att->section_id.' date='.$att->date);

        // Infer school if attendance record lacks it
        $schoolId = $att->school_id;
        if (! $schoolId) {
            // Try section
            $sec = \App\Models\Section::find($att->section_id);
            if ($sec && $sec->school_id) { $schoolId = $sec->school_id; }
            else {
                // Try enrollment for first student
                $en = \App\Models\StudentEnrollment::where('student_id', $att->student_id)->first();
                if ($en) { $schoolId = $en->school_id; }
            }
        }
        if (! $schoolId) {
            $this->error('Unable to determine school for this attendance record');
            return 1;
        }

        $settings = Setting::forSchool($schoolId)->where('key','like','sms_class_attendance_%')->pluck('value','key')->toArray();
        $this->info('SMS settings (sms_class_attendance_*):');
        foreach ($settings as $k=>$v) { $this->line("  $k => $v"); }

        $rows = Attendance::where('class_id',$att->class_id)->where('section_id',$att->section_id)->where('date',$att->date)->get();
        $payload = [];
        foreach ($rows as $r) { $payload[$r->student_id] = ['status' => $r->status]; }
        $this->info('Built payload with '.count($payload).' students');

        $school = School::find($schoolId);
        $svc = new AttendanceSmsService();
        $res = $svc->enqueueAttendanceSms($school, $payload, $att->class_id, $att->section_id, $att->date, true, []);
        $this->info('enqueueAttendanceSms result: '.json_encode($res));

        if (!empty($res['skipped'])) {
            $this->info('Skipped samples (first 10):');
            foreach (array_slice($res['skipped'],0,10) as $s) {
                $this->line('  '.json_encode($s));
            }
        }

        return 0;
    }
}
