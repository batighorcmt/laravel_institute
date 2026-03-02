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
        $classes = \App\Models\SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        return view('principal.documents.prottayon.index', compact('school','classes'));
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
            'class_id' => 'required',
            'section_id' => 'nullable',
            'student_id' => 'required',
            'attestation_type' => 'required|string',
            'template_id' => 'nullable|integer',
            'content' => 'required|string',
            'is_final' => 'nullable|boolean',
            'updated_student_data' => 'nullable|array'
        ]);

        $student = Student::forSchool($school->id)->where('student_id', $validated['student_id'])->firstOrFail();

        // Optional: Update student record if data was edited in Vue
        if (!empty($validated['updated_student_data'])) {
            $student->update(array_intersect_key(
                $validated['updated_student_data'], 
                array_flip(['student_name_bn', 'student_name_en', 'father_name', 'mother_name', 'father_name_bn', 'mother_name_bn', 'date_of_birth', 'present_village', 'present_post_office'])
            ));
        }

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
                'template_id' => $validated['template_id'] ?? null,
                'custom_content' => $request->has('is_final') 
                                    ? $validated['content'] 
                                    : $this->parseTemplate($school, $student, $validated['content']),
                'layout' => $request->get('layout', 'standard'),
                'is_final' => (bool)$request->get('is_final', false),
            ],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('principal.institute.documents.prottayon.print', [$school, $record->id])
            ]);
        }

        return redirect()->route('principal.institute.documents.prottayon.print', [$school, $record->id]);
    }

    private function parseTemplate($school, $student, $content)
    {
        if (!$content) return '';
        
        $enrollment = $student->enrollments->where('status', 'active')->first() ?? $student->enrollments->first();
        
        $tokens = [
            '[student_name_bn]' => $student->student_name_bn ?: $student->name,
            '[student_name_en]' => $student->student_name_en ?: '',
            '[father_name_bn]' => $student->father_name_bn ?: '',
            '[father_name_en]' => $student->father_name ?: '',
            '[mother_name_bn]' => $student->mother_name_bn ?: '',
            '[mother_name_en]' => $student->mother_name ?: '',
            '[class_name]' => $enrollment->class->name ?? '',
            '[section_name]' => $enrollment->section->name ?? '',
            '[roll_no]' => $enrollment->roll_no ?? '',
            '[student_id]' => $student->student_id,
            '[session]' => $enrollment->academicYear->name ?? '',
            '[date_of_birth]' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
            '[gender]' => $student->gender == 'male' ? 'ছাত্র' : 'ছাত্রী',
            '[blood_group]' => $student->blood_group ?: '',
            '[present_village]' => $student->present_village ?: '',
            '[present_post_office]' => $student->present_post_office ?: '',
            '[present_upazilla]' => $student->present_upazilla ?: '',
            '[present_district]' => $student->present_district ?: '',
            '[permanent_village]' => $student->permanent_village ?: '',
            '[permanent_post_office]' => $student->permanent_post_office ?: '',
            '[permanent_upazilla]' => $student->permanent_upazilla ?: '',
            '[permanent_district]' => $student->permanent_district ?: '',
            '[date]' => date('d/m/Y'),
            '[school_name]' => $school->name,
        ];

        // Legacy/Simplified mappings
        $tokens['[student_name]'] = $tokens['[student_name_bn]'];
        $tokens['[father_name]'] = $tokens['[father_name_bn]'];
        $tokens['[mother_name]'] = $tokens['[mother_name_bn]'];

        return str_replace(array_keys($tokens), array_values($tokens), $content);
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
