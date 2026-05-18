<?php

use App\Jobs\SendSmsChunkJob;
use App\Models\School;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AttendanceSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('does not send on first-time attendance when setting disabled', function () {
    Bus::fake();

    $school = School::create(['name' => 'Test School', 'status' => 'active']);

    $student = Student::create([
        'school_id' => $school->id,
        'student_name_en' => 'John Doe',
        'student_name_bn' => 'জন ডো',
        'date_of_birth' => '2010-01-01',
        'gender' => 'male',
        'father_name' => 'Father',
        'mother_name' => 'Mother',
        'guardian_phone' => '01700000000',
        'admission_date' => '2020-01-01',
        'status' => 'active',
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_id' => $school->id,
        'academic_year_id' => 1,
        'class_id' => 1,
        'section_id' => 1,
        'roll_no' => 1,
        'status' => 'active',
    ]);

    // Disable present SMS for class attendance
    Setting::create(['school_id' => $school->id, 'key' => 'sms_class_attendance_present', 'value' => '0']);

    $svc = new AttendanceSmsService;
    $res = $svc->enqueueAttendanceSms($school, [$student->id => ['status' => 'present']], 1, 1, now()->toDateString(), false, [], null, 'class');

    expect($res['sent'])->toBe(0);
    Bus::assertNotDispatched(SendSmsChunkJob::class);
});

it('sends on first-time attendance when setting enabled', function () {
    Bus::fake();

    $school = School::create(['name' => 'Test School', 'status' => 'active']);

    $student = Student::create([
        'school_id' => $school->id,
        'student_name_en' => 'Jane Doe',
        'student_name_bn' => 'জেন ডো',
        'date_of_birth' => '2010-01-01',
        'gender' => 'female',
        'father_name' => 'Father',
        'mother_name' => 'Mother',
        'guardian_phone' => '01700000001',
        'admission_date' => '2020-01-01',
        'status' => 'active',
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_id' => $school->id,
        'academic_year_id' => 1,
        'class_id' => 1,
        'section_id' => 1,
        'roll_no' => 2,
        'status' => 'active',
    ]);

    // Enable present SMS for class attendance
    Setting::create(['school_id' => $school->id, 'key' => 'sms_class_attendance_present', 'value' => '1']);

    $svc = new AttendanceSmsService;
    $res = $svc->enqueueAttendanceSms($school, [$student->id => ['status' => 'present']], 1, 1, now()->toDateString(), false, [], null, 'class');

    expect($res['sent'])->toBe(1);
    Bus::assertDispatched(SendSmsChunkJob::class);
});

it('sends on update even if setting disabled', function () {
    Bus::fake();

    $school = School::create(['name' => 'Test School', 'status' => 'active']);

    $student = Student::create([
        'school_id' => $school->id,
        'student_name_en' => 'Jim Beam',
        'student_name_bn' => 'জিম বিম',
        'date_of_birth' => '2010-01-01',
        'gender' => 'male',
        'father_name' => 'Father',
        'mother_name' => 'Mother',
        'guardian_phone' => '01700000002',
        'admission_date' => '2020-01-01',
        'status' => 'active',
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_id' => $school->id,
        'academic_year_id' => 1,
        'class_id' => 1,
        'section_id' => 1,
        'roll_no' => 3,
        'status' => 'active',
    ]);

    // Disable present SMS for class attendance
    Setting::create(['school_id' => $school->id, 'key' => 'sms_class_attendance_present', 'value' => '0']);

    $svc = new AttendanceSmsService;
    // Simulate an update where old status exists and changes from absent -> present
    $res = $svc->enqueueAttendanceSms($school, [$student->id => ['status' => 'present']], 1, 1, now()->toDateString(), true, [$student->id => 'absent'], null, 'class');

    expect($res['sent'])->toBe(1);
    Bus::assertDispatched(SendSmsChunkJob::class);
});

it('sends extra class attendance when setting enabled', function () {
    Bus::fake();

    $school = School::create(['name' => 'Test School', 'status' => 'active']);

    $student = Student::create([
        'school_id' => $school->id,
        'student_name_en' => 'Extra Class Kid',
        'student_name_bn' => 'অতিরিক্ত শ্রেণি',
        'date_of_birth' => '2010-01-01',
        'gender' => 'male',
        'father_name' => 'Father',
        'mother_name' => 'Mother',
        'guardian_phone' => '01700000004',
        'admission_date' => '2020-01-01',
        'status' => 'active',
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_id' => $school->id,
        'academic_year_id' => 1,
        'class_id' => 1,
        'section_id' => 1,
        'roll_no' => 5,
        'status' => 'active',
    ]);

    Setting::create(['school_id' => $school->id, 'key' => 'sms_extra_class_attendance_absent', 'value' => '1']);

    $svc = new AttendanceSmsService;
    $res = $svc->enqueueAttendanceSms($school, [$student->id => ['status' => 'absent']], 1, 1, now()->toDateString(), false, [], null, 'extra_class');

    expect($res['sent'])->toBe(1);
    Bus::assertDispatched(SendSmsChunkJob::class);
});

it('sends absent on first-time when setting enabled', function () {
    Bus::fake();

    $school = School::create(['name' => 'Test School', 'status' => 'active']);

    $student = Student::create([
        'school_id' => $school->id,
        'student_name_en' => 'Absent Child',
        'student_name_bn' => 'অনুপস্থিত শিশু',
        'date_of_birth' => '2010-01-01',
        'gender' => 'male',
        'father_name' => 'Father',
        'mother_name' => 'Mother',
        'guardian_phone' => '01700000003',
        'admission_date' => '2020-01-01',
        'status' => 'active',
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_id' => $school->id,
        'academic_year_id' => 1,
        'class_id' => 1,
        'section_id' => 1,
        'roll_no' => 4,
        'status' => 'active',
    ]);

    // Enable absent SMS for class attendance
    Setting::create(['school_id' => $school->id, 'key' => 'sms_class_attendance_absent', 'value' => '1']);

    $svc = new AttendanceSmsService;
    $res = $svc->enqueueAttendanceSms($school, [$student->id => ['status' => 'absent']], 1, 1, now()->toDateString(), false, [], null, 'class');

    expect($res['sent'])->toBe(1);
    Bus::assertDispatched(SendSmsChunkJob::class);
});
