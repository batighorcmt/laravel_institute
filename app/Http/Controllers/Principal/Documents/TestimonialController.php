<?php

namespace App\Http\Controllers\Principal\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentRecord;
use App\Models\DocumentSetting;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\DocumentMemoService;

class TestimonialController extends Controller
{
    public function index(Request $request, School $school)
    {
        $academicYears = \App\Models\AcademicYear::forSchool($school->id)->orderBy('start_date', 'desc')->get();
        return view('principal.documents.testimonial.index', compact('school', 'academicYears'));
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
            'exam_name' => 'required|string', // SSC/HSC
            'academic_year' => 'required|integer',
            'session' => 'required|string',
            'passing_year' => 'required|integer',
            'result' => 'nullable|string',
            'roll' => 'nullable|string',
            'registration' => 'nullable|string',
            'center' => 'nullable|string',
        ]);

        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);
        $academicYear = \App\Models\AcademicYear::find($validated['academic_year']);

        // Memo: schoolCode/testimonial/academicYearName/serialInYear
        $memoNo = DocumentMemoService::generate($school, 'testimonial', null, $academicYear->name);

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
                'session' => $validated['session'],
                'passing_year' => $validated['passing_year'],
                'result' => $validated['result'] ?? null,
                'roll' => $validated['roll'] ?? null,
                'registration' => $validated['registration'] ?? null,
                'center' => $validated['center'] ?? null,
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
        return view('principal.documents.testimonial.edit', compact('school','document'));
    }

    public function update(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'testimonial', 404);
        $validated = $request->validate([
            'student_id' => 'required|integer',
            'exam_name' => 'required|string',
            'academic_year' => 'required|integer',
            'session' => 'required|string',
            'passing_year' => 'required|integer',
            'result' => 'nullable|string',
            'roll' => 'nullable|string',
            'registration' => 'nullable|string',
            'center' => 'nullable|string',
        ]);
        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);
        $document->update([
            'student_id' => $student->id,
            'data' => [
                'exam_name' => $validated['exam_name'],
                'academic_year' => $validated['academic_year'],
                'session' => $validated['session'],
                'passing_year' => $validated['passing_year'],
                'result' => $validated['result'] ?? null,
                'roll' => $validated['roll'] ?? null,
                'registration' => $validated['registration'] ?? null,
                'center' => $validated['center'] ?? null,
            ],
        ]);
        return redirect()->route('principal.institute.documents.testimonial.print', [$school, $document->id])
            ->with('success','Testimonial updated');
    }
}
