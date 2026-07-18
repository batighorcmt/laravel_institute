<?php

namespace App\Http\Controllers\Principal\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentRecord;
use App\Models\DocumentSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentPublicExam;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\DocumentMemoService;

class TestimonialController extends Controller
{
    public function index(Request $request, School $school)
    {
        $academicYears = \App\Models\AcademicYear::forSchool($school->id)->orderBy('start_date', 'desc')->get();
        $classes = \App\Models\SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        $publicExams = \App\Models\PublicExam::where('school_id', $school->id)->where('status', 'active')->get();

        return view('principal.documents.testimonial.index', compact('school', 'academicYears', 'classes', 'publicExams'));
    }

    /**
     * AJAX: Load students with their public exam data for the testimonial generation table
     */
    public function loadStudents(Request $request, School $school)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id'         => 'required|exists:classes,id',
            'public_exam_name' => 'required|string',
        ]);

        $academicYearId = $request->academic_year_id;
        $classId        = $request->class_id;
        $publicExamName = $request->public_exam_name;
        $studentId      = $request->student_id;

        $query = Student::where('school_id', $school->id)
            ->whereHas('enrollments', function($q) use ($academicYearId, $classId) {
                $q->where('academic_year_id', $academicYearId)
                  ->where('class_id', $classId)
                  ->where('status', 'active');
            })
            ->with([
                'enrollments' => function($q) use ($academicYearId, $classId) {
                    $q->where('academic_year_id', $academicYearId)
                      ->where('class_id', $classId)
                      ->where('status', 'active');
                },
                'publicExams',
            ]);

        // Filter by specific student if provided
        if ($studentId) {
            $query->where('id', (int)$studentId);
        }

        $students = $query->get()
            ->sortBy(function($student) {
                $enrollment = $student->enrollments->first();
                return $enrollment ? $enrollment->roll_no : 999999;
            })
            ->values();

        $academicYear = \App\Models\AcademicYear::find($academicYearId);

        $result = $students->map(function($student) use ($publicExamName, $academicYear) {
            $enrollment = $student->enrollments->first();
            $peData = $student->publicExams->where('exam_name', $publicExamName)->first();

            // Check if testimonial already exists for this student
            $existingDoc = DocumentRecord::where('school_id', $student->school_id)
                ->where('student_id', $student->id)
                ->where('type', 'testimonial')
                ->latest('issued_at')
                ->first();

            return [
                'id'              => $student->id,
                'name'            => $student->student_name_bn ?: $student->student_name_en,
                'name_en'         => $student->student_name_en,
                'father_name'     => $student->father_name,
                'roll_no'         => $enrollment ? $enrollment->roll_no : '',
                'student_id'      => $student->student_id,
                'exam_name'       => $publicExamName,
                'board'           => $peData ? $peData->board : '',
                'roll_no_pub'     => $peData ? $peData->roll_no : '',
                'reg_no'          => $peData ? ($peData->reg_no ?: $student->board_registration_no) : ($student->board_registration_no ?: ''),
                'exam_year'       => $peData ? $peData->exam_year : '',
                'session'         => $peData ? $peData->session : '',
                'center_name'     => $peData ? $peData->center_name : '',
                'result'          => $peData ? $peData->result : '',
                'has_public_exam' => $peData ? true : false,
                'has_testimonial' => $existingDoc ? true : false,
                'testimonial_id'  => $existingDoc ? $existingDoc->id : null,
            ];
        });

        return response()->json(['students' => $result]);
    }

    /**
     * Quick generate testimonial from public exam data (AJAX)
     */
    public function quickGenerate(Request $request, School $school)
    {
        $validated = $request->validate([
            'student_id'       => 'required|integer',
            'academic_year_id' => 'required|integer',
            'exam_name'        => 'required|string',
        ]);

        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);
        $academicYear = \App\Models\AcademicYear::find($validated['academic_year_id']);
        $academicYearName = $academicYear ? $academicYear->name : (string)$validated['academic_year_id'];

        // Get public exam data for this student
        $peData = StudentPublicExam::where('student_id', $student->id)
            ->where('exam_name', $validated['exam_name'])
            ->first();

        $board    = $peData->board ?? '';
        $session  = $peData->session ?? '';
        $roll     = $peData->roll_no ?? '';
        $regNo    = $peData->reg_no ?? $student->board_registration_no ?? '';
        $examYear = $peData->exam_year ?? '';
        $center   = $peData->center_name ?? '';
        $group    = $this->resolveGroupName($student, $peData);

        // Memo generation
        $memoNo = DocumentMemoService::generate($school, 'testimonial', null, $academicYearName, null, $student, 'en');

        $record = DocumentRecord::create([
            'school_id'  => $school->id,
            'student_id' => $student->id,
            'type'       => 'testimonial',
            'memo_no'    => $memoNo,
            'issued_at'  => Carbon::now(),
            'code'       => Str::uuid()->toString(),
            'data'       => [
                'exam_name'    => $validated['exam_name'],
                'academic_year' => $validated['academic_year_id'],
                'board'        => $board,
                'session'      => $session,
                'passing_year' => $examYear,
                'result'       => $peData->result ?? null,
                'roll'         => $roll,
                'registration' => $regNo,
                'center'       => $center,
                'group'        => $group,
            ],
        ]);

        return response()->json([
            'success'  => true,
            'record_id' => $record->id,
            'print_url' => route('principal.institute.documents.testimonial.print', [$school, $record->id]),
        ]);
    }

    public function history(Request $request, School $school)
    {
        $records = DocumentRecord::where('school_id',$school->id)
            ->where('type','testimonial')
            ->latest('issued_at')
            ->paginate(25);
        return view('principal.documents.testimonial.history', compact('school','records'));
    }

    public function generate(Request $request, School $school)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer',
            'exam_name' => 'required|string',
            'academic_year' => 'required|integer',
            'board' => 'required|string',
            'session' => 'required|string',
            'passing_year' => 'required|integer',
            'result' => 'nullable|string',
            'roll' => 'nullable|string',
            'registration' => 'nullable|string',
            'center' => 'nullable|string',
        ]);

        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);
        $academicYear = \App\Models\AcademicYear::find($validated['academic_year']);
        $academicYearName = $academicYear ? $academicYear->name : (string)$validated['academic_year'];

        $peData = StudentPublicExam::where('student_id', $student->id)
            ->where('exam_name', $validated['exam_name'])
            ->first();

        $memoNo = DocumentMemoService::generate($school, 'testimonial', null, $academicYearName, null, $student, 'en');

        $record = DocumentRecord::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'type' => 'testimonial',
            'memo_no' => $memoNo,
            'issued_at' => Carbon::now(),
            'code' => Str::uuid()->toString(),
            'data' => [
                'exam_name' => $validated['exam_name'],
                'academic_year' => $validated['academic_year'],
                'board' => $validated['board'],
                'session' => $validated['session'],
                'passing_year' => $validated['passing_year'],
                'result' => $validated['result'] ?? null,
                'roll' => $validated['roll'] ?? null,
                'registration' => $validated['registration'] ?? null,
                'center' => $validated['center'] ?? null,
                'group' => $this->resolveGroupName($student, $peData),
            ],
        ]);

        return redirect()->route('principal.institute.documents.testimonial.print', [$school, $record->id]);
    }

    public function print(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'testimonial', 404);
        $setting = DocumentSetting::where('school_id',$school->id)->where('page','testimonial')->first();
        return view('principal.documents.testimonial.print', [
            'school' => $school,
            'document' => $document,
            'student' => $document->student,
            'setting' => $setting,
        ]);
    }

    public function edit(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'testimonial', 404);
        $academicYears = \App\Models\AcademicYear::forSchool($school->id)->orderBy('start_date', 'desc')->get();

        // Academic year / board / class / section / student / exam name are now
        // fixed (display-only) on this form — class/section are derived live
        // from the student's current enrollment purely for that display, since
        // they were never actually persisted on the document. Groups offered
        // for selection are scoped to the student's current class.
        $student = $document->student;
        $enrollment = $student?->currentEnrollment;
        $groups = $enrollment
            ? \App\Models\Group::forSchool($school->id)->where('class_id', $enrollment->class_id)->where('status', 'active')->get(['id', 'name', 'bangla_name'])
            : collect();

        return view('principal.documents.testimonial.edit', compact('school', 'document', 'academicYears', 'enrollment', 'groups'));
    }

    public function update(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'testimonial', 404);
        $validated = $request->validate([
            'student_id' => 'required|integer',
            'exam_name' => 'required|string',
            'academic_year' => 'required|integer',
            'board' => 'required|string',
            'session' => 'required|string',
            'passing_year' => 'required|integer',
            'result' => 'nullable|string',
            'roll' => 'nullable|string',
            'registration' => 'nullable|string',
            'center' => 'nullable|string',
            'group' => 'nullable|string|max:255',
        ]);
        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);
        $document->update([
            'student_id' => $student->id,
            'data' => [
                'exam_name' => $validated['exam_name'],
                'academic_year' => $validated['academic_year'],
                'board' => $validated['board'],
                'session' => $validated['session'],
                'passing_year' => $validated['passing_year'],
                'result' => $validated['result'] ?? null,
                'roll' => $validated['roll'] ?? null,
                'registration' => $validated['registration'] ?? null,
                'center' => $validated['center'] ?? null,
                'group' => $validated['group'] ?? null,
            ],
        ]);
        return redirect()->route('principal.institute.documents.testimonial.print', [$school, $document->id])
            ->with('success','Testimonial updated');
    }

    /**
     * Resolve a display-ready group name for testimonial generation: prefer
     * the group saved against this exam on the public-exam-info page, falling
     * back to the student's current class enrollment's group.
     */
    protected function resolveGroupName(Student $student, ?StudentPublicExam $peData): ?string
    {
        $groupId = ($peData && $peData->group_id) ? $peData->group_id : $student->currentEnrollment?->group_id;
        if (! $groupId) {
            return null;
        }
        $group = \App\Models\Group::find($groupId);
        return $group ? ($group->bangla_name ?: $group->name) : null;
    }
}
