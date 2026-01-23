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

class ProttayonController extends Controller
{
    public function index(Request $request, School $school)
    {
        // Basic data for form: classes, sections, active students
        $classes = \App\Models\SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        $sections = \App\Models\Section::where('school_id', $school->id)->orderBy('name')->get();
        return view('principal.documents.prottayon.index', compact('school','classes','sections'));
    }

    public function history(Request $request, School $school)
    {
        $records = DocumentRecord::where('school_id',$school->id)
            ->where('type','prottayon')
            ->latest('issued_at')
            ->paginate(25);
        return view('principal.documents.prottayon.history', compact('school','records'));
    }

    public function generate(Request $request, School $school)
    {
        $validated = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'nullable|integer',
            'student_id' => 'required|integer',
            'attestation_type' => 'required|string',
        ]);

        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);

        // Memo: schoolCode<>prottayon<>academicYearName<>serialInYear
        $memoNo = DocumentMemoService::generate($school, 'prottayon', null, null, null, $student);

        $record = DocumentRecord::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'type' => 'prottayon',
            'memo_no' => $memoNo,
            'issued_at' => Carbon::now(),
            'code' => Str::uuid()->toString(),
            'data' => [
                'attestation_type' => $validated['attestation_type'],
                'class_id' => $validated['class_id'],
                'section_id' => $validated['section_id'] ?? null,
            ],
        ]);

        return redirect()->route('principal.institute.documents.prottayon.print', [$school, $record->id]);
    }

    public function print(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'prottayon', 404);
        $setting = DocumentSetting::where('school_id',$school->id)->where('page','prottayon')->first();
        return view('principal.documents.prottayon.print', [
            'school' => $school,
            'document' => $document,
            'student' => $document->student,
            'setting' => $setting,
        ]);
    }

    public function edit(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'prottayon', 404);
        $classes = \App\Models\SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        $sections = \App\Models\Section::where('school_id', $school->id)->orderBy('name')->get();
        return view('principal.documents.prottayon.edit', compact('school','document','classes','sections'));
    }

    public function update(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'prottayon', 404);
        $validated = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'nullable|integer',
            'student_id' => 'required|integer',
            'attestation_type' => 'required|string',
        ]);
        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);
        $document->update([
            'student_id' => $student->id,
            'data' => [
                'attestation_type' => $validated['attestation_type'],
                'class_id' => $validated['class_id'],
                'section_id' => $validated['section_id'] ?? null,
            ],
        ]);
        return redirect()->route('principal.institute.documents.prottayon.print', [$school, $document->id])
            ->with('success','প্রত্যয়নপত্র হালনাগাদ হয়েছে');
    }
}
