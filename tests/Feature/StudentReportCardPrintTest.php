<?php

use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

it('registers student report card print routes', function () {
    expect(Route::has('principal.institute.students.report-cards.print'))->toBeTrue();
    expect(Route::has('principal.institute.students.report-cards.print-all'))->toBeTrue();
});

it('renders report card print content with lesson evaluation total summary', function () {
    $school = School::make([
        'id' => 1,
        'name' => 'Test School',
        'name_bn' => 'টেস্ট স্কুল',
    ]);

    $student = Student::make([
        'id' => 177,
        'student_id' => '2026001',
        'student_name_bn' => 'টেস্ট শিক্ষার্থী',
        'status' => 'active',
    ]);

    $student->currentEnrollment = StudentEnrollment::make([
        'roll_no' => 5,
    ]);

    $html = view('principal.students.report-card-print-content', [
        'school' => $school,
        'student' => $student,
        'startDate' => Carbon::parse('2026-06-07'),
        'endDate' => Carbon::parse('2026-06-11'),
        'attendanceSummary' => [
            'total_working_days' => 5,
            'present' => 4,
            'absent' => 1,
            'late' => 0,
        ],
        'monthlyAttendance' => collect([
            'June 2026' => ['total' => 5, 'present' => 4, 'absent' => 1],
        ]),
        'lessonSummary' => [
            'completed' => 8,
            'partial' => 2,
            'not_done' => 3,
            'absent' => 1,
        ],
        'subjectWiseEvaluation' => collect([
            'Bangla' => [
                'completed' => 4,
                'partial' => 1,
                'not_done' => 1,
                'absent' => 0,
                'total' => 6,
            ],
        ]),
        'exams' => collect(),
        'examsData' => [],
    ])->render();

    expect($html)
        ->toContain('মোট সারাংশ')
        ->toContain('পড়া হয়েছে + আংশিক (মোট)')
        ->toContain('পড়া হয়নি + অনুপস্থিত (মোট)')
        ->toContain('report-card-signature-note');
});

it('renders report card print page with overlay settings menu', function () {
    $school = School::make([
        'id' => 1,
        'name' => 'Test School',
        'name_bn' => 'টেস্ট স্কুল',
    ]);

    $student = Student::make([
        'id' => 177,
        'student_id' => '2026001',
        'student_name_bn' => 'টেস্ট শিক্ষার্থী',
        'status' => 'active',
    ]);

    $student->currentEnrollment = StudentEnrollment::make([
        'roll_no' => 5,
    ]);

    $html = view('principal.students.report-card-print', [
        'school' => $school,
        'student' => $student,
        'startDate' => Carbon::parse('2026-06-07'),
        'endDate' => Carbon::parse('2026-06-11'),
        'attendanceSummary' => [
            'total_working_days' => 5,
            'present' => 4,
            'absent' => 1,
            'late' => 0,
        ],
        'monthlyAttendance' => collect([
            'June 2026' => ['total' => 5, 'present' => 4, 'absent' => 1],
        ]),
        'lessonSummary' => [
            'completed' => 8,
            'partial' => 2,
            'not_done' => 3,
            'absent' => 1,
        ],
        'subjectWiseEvaluation' => collect([
            'Bangla' => [
                'completed' => 4,
                'partial' => 1,
                'not_done' => 1,
                'absent' => 0,
                'total' => 6,
            ],
        ]),
        'exams' => collect(),
        'examsData' => [],
    ])->render();

    expect($html)
        ->toContain('printOverlaySettings')
        ->toContain('showSignatureNote')
        ->toContain('signatureNoteInput')
        ->toContain('প্রিন্ট সেটিংস');
});
