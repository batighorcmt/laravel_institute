<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Services\AttendanceSmsService;

class TestAttendanceSms extends Command
{
    protected $signature = 'test:attendance-sms {--school=} {--section=} {--date=}';
    protected $description = 'Simulate class attendance and enqueue attendance SMS (for testing)';

    public function handle()
    {
        $schoolId = $this->option('school');
        $sectionId = $this->option('section');
        $date = $this->option('date') ?: now()->toDateString();

        $school = $schoolId ? School::find($schoolId) : School::first();
        if (! $school) {
            $this->error('No school found in database');
            return 1;
        }

        $section = $sectionId ? Section::find($sectionId) : Section::where('school_id', $school->id)->first();
        if (! $section) {
            $this->error('No section found for school '.$school->id);
            return 1;
        }

        $enrolled = StudentEnrollment::where([['school_id', $school->id], ['class_id', $section->class_id], ['section_id', $section->id], ['status','active']])->pluck('student_id');
        if ($enrolled->isEmpty()) {
            $this->error('No enrolled active students found for section '.$section->id);
            return 1;
        }

        $attendancePayload = [];
        $toggle = true;
        foreach ($enrolled as $sid) {
            // alternate present/absent for test
            $attendancePayload[$sid] = ['status' => $toggle ? 'present' : 'absent'];
            $toggle = ! $toggle;
        }

        $this->info('Using school: '.$school->id.' ('.$school->name.')');
        $this->info('Using section: '.$section->id.' (class '.$section->class_id.')');
        $this->info('Date: '.$date);
        $this->info('Students in payload: '.count($attendancePayload));

        $svc = new AttendanceSmsService();
        $res = $svc->enqueueAttendanceSms($school, $attendancePayload, $section->class_id, $section->id, $date, false, []);

        $this->info('Result: '.json_encode($res));
        return 0;
    }
}
