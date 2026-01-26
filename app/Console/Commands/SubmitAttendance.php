<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\Attendance;
use App\Services\AttendanceSmsService;

class SubmitAttendance extends Command
{
    protected $signature = 'submit:attendance {school_id} {class_id} {section_id} {date?}';
    protected $description = 'Submit attendance for a class/section/date and enqueue SMS';

    public function handle()
    {
        $schoolId = (int)$this->argument('school_id');
        $classId = (int)$this->argument('class_id');
        $sectionId = (int)$this->argument('section_id');
        $date = $this->argument('date') ?: now()->toDateString();

        $school = School::find($schoolId);
        if (! $school) { $this->error('School not found'); return 1; }

        $enrolled = StudentEnrollment::where(['school_id'=>$schoolId,'class_id'=>$classId,'section_id'=>$sectionId,'status'=>'active'])->pluck('student_id')->toArray();
        if (empty($enrolled)) { $this->error('No enrolled students found'); return 1; }

        $this->info('Enrolled: '.count($enrolled));
        $payload = [];
        $i = 0;
        foreach ($enrolled as $sid) {
            $status = ($i % 2 === 0) ? 'present' : 'absent';
            Attendance::updateOrCreate([
                'student_id' => $sid,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'date' => $date,
            ],[
                'status' => $status,
                'recorded_by' => null,
            ]);
            $payload[$sid] = ['status' => $status];
            $i++;
        }

        $this->info('Inserted/updated attendance: '.count($payload));
        $svc = new AttendanceSmsService();
        $res = $svc->enqueueAttendanceSms($school, $payload, $classId, $sectionId, $date, false, [], null);
        $this->info('Enqueue result: '.json_encode($res));
        return 0;
    }
}
