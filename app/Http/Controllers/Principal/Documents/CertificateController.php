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

class CertificateController extends Controller
{
    public function index(Request $request, School $school)
    {
        $classes = \App\Models\SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        return view('principal.documents.certificate.index', compact('school','classes'));
    }

    public function history(Request $request, School $school)
    {
        $records = DocumentRecord::where('school_id',$school->id)
            ->where('type','certificate')
            ->latest('issued_at')
            ->paginate(25);
        return view('principal.documents.certificate.history', compact('school','records'));
    }

    public function generate(Request $request, School $school)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer',
            'class_name' => 'required|string',
            'year' => 'required|integer',
            'certificate_title' => 'required|string',
        ]);

        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);

        // Memo: schoolCode<>certificate<>academicYearName<>serialInYear
        $memoNo = DocumentMemoService::generate($school, 'certificate');

        $record = DocumentRecord::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'type' => 'certificate',
            'memo_no' => $memoNo,
            'issued_at' => Carbon::now(),
            'code' => Str::uuid()->toString(),
            'data' => [
                'class_name' => $validated['class_name'],
                'year' => $validated['year'],
                'certificate_title' => $validated['certificate_title'],
            ],
        ]);

        return redirect()->route('principal.institute.documents.certificate.print', [$school, $record->id]);
    }

    public function print(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'certificate', 404);
        $setting = DocumentSetting::where('school_id',$school->id)->where('page','certificate')->first();
        return view('principal.documents.certificate.print', [
            'school' => $school,
            'document' => $document,
            'student' => $document->student,
            'setting' => $setting,
        ]);
    }

    public function edit(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'certificate', 404);
        return view('principal.documents.certificate.edit', compact('school','document'));
    }

    public function update(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'certificate', 404);
        $validated = $request->validate([
            'student_id' => 'required|integer',
            'class_name' => 'required|string',
            'year' => 'required|integer',
            'certificate_title' => 'required|string',
        ]);
        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);
        $document->update([
            'student_id' => $student->id,
            'data' => [
                'class_name' => $validated['class_name'],
                'year' => $validated['year'],
                'certificate_title' => $validated['certificate_title'],
            ],
        ]);
        return redirect()->route('principal.institute.documents.certificate.print', [$school, $document->id])
            ->with('success','Certificate updated');
    }
}
