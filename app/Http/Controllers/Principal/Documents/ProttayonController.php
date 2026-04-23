<?php

namespace App\Http\Controllers\Principal\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentRecord;
use App\Models\DocumentSetting;
use App\Models\DocumentTemplate;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
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
            'attestation_type' => 'nullable|string',
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

        $lang = 'bn';
        if (!empty($validated['template_id'])) {
            $template = DocumentTemplate::find($validated['template_id']);
            if ($template) {
                $lang = $template->language;
            }
        }

        // Memo: schoolCode<>prottayon<>academicYearName<>serialInYear
        $memoNo = DocumentMemoService::generate($school, 'prottayon', null, null, null, $student, $lang);

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
                                    : $this->parseTemplate($school, $student, $validated['content'], $validated['template_id'] ?? null),
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

    private function parseTemplate($school, $student, $content, $templateId = null)
    {
        if (!$content) return '';

        $template = $templateId ? DocumentTemplate::find($templateId) : null;
        $lang = $template ? $template->language : 'bn';

        $enrollment = $student->enrollments->where('status', 'active')->first() ?? $student->enrollments->first();

        $tokens = [
            '[student_name_bn]' => $student->student_name_bn ?: $student->name,
            '[student_name_en]' => $student->student_name_en ?: '',
            '[father_name_bn]' => $student->father_name_bn ?: '',
            '[father_name_en]' => $student->father_name ?: '',
            '[mother_name_bn]' => $student->mother_name_bn ?: '',
            '[mother_name_en]' => $student->mother_name ?: '',
            '[class_name_bn]' => $enrollment->class->bangla_name ?: ($enrollment->class->name ?? ''),
            '[class_name_en]' => $enrollment->class->name ?? '',
            '[section_name_bn]' => $enrollment->section->bangla_name ?: ($enrollment->section->name ?? ''),
            '[section_name_en]' => $enrollment->section->name ?? '',
            '[roll_no_bn]' => $enrollment ? toBengaliNumber($enrollment->roll_no) : '',
            '[roll_no_en]' => $enrollment ? $enrollment->roll_no : '',
            '[student_id]' => $student->student_id,
            '[session_bn]' => $enrollment->academicYear->name_bn ?: ($enrollment->academicYear->name ?? ''),
            '[session_en]' => $enrollment->academicYear->name ?? '',
            '[date_of_birth]' => $student->date_of_birth ? ($lang === 'en' ? $student->date_of_birth->format('d/m/Y') : toBengaliNumber($student->date_of_birth->format('d/m/Y'))) : '',
            '[date_of_birth_bn]' => $student->date_of_birth ? toBengaliNumber($student->date_of_birth->format('d/m/Y')) : '',
            '[date_of_birth_en]' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
            '[gender]' => $student->gender == 'male' ? 'ছাত্র' : 'ছাত্রী',
            '[blood_group]' => $student->blood_group ?: '',
            '[present_village_bn]' => $student->present_village ?: '',
            '[present_village_en]' => $student->present_village_en ?: '',
            '[present_post_office_bn]' => $student->present_post_office ?: '',
            '[present_post_office_en]' => $student->present_post_office_en ?: '',
            '[present_upazilla_bn]' => $student->present_upazilla ?: '',
            '[present_upazilla_en]' => $student->present_upazilla_en ?: '',
            '[present_district_bn]' => $student->present_district ?: '',
            '[present_district_en]' => $student->present_district_en ?: '',
            '[permanent_village_bn]' => $student->permanent_village ?: '',
            '[permanent_village_en]' => $student->permanent_village_en ?: '',
            '[permanent_post_office_bn]' => $student->permanent_post_office ?: '',
            '[permanent_post_office_en]' => $student->permanent_post_office_en ?: '',
            '[permanent_upazilla_bn]' => $student->permanent_upazilla ?: '',
            '[permanent_upazilla_en]' => $student->permanent_upazilla_en ?: '',
            '[permanent_district_bn]' => $student->permanent_district ?: '',
            '[permanent_district_en]' => $student->permanent_district_en ?: '',
            '[guardian_phone]' => $student->guardian_phone ?: '',
            '[date]' => $lang === 'en' ? date('d/m/Y') : toBengaliNumber(date('d/m/Y')),
            '[school_name_bn]' => $school->name_bn ?: $school->name,
            '[school_name_en]' => $school->name,
        ];

        // Language specific defaults
        if ($lang === 'en') {
            $tokens['[session]'] = $enrollment->academicYear->name ?? '';
            $tokens['[school_name]'] = $school->name;
            $tokens['[student_name]'] = $student->student_name_en ?: $student->student_name_bn;
            $tokens['[father_name]'] = $student->father_name ?: $student->father_name_bn;
            $tokens['[mother_name]'] = $student->mother_name ?: $student->mother_name_bn;
            $tokens['[roll_no]'] = $enrollment->roll_no ?? '';
            $tokens['[class_name]'] = $enrollment->class->name ?? '';
            $tokens['[section_name]'] = $enrollment->section->name ?? '';
        } else {
            $tokens['[session]'] = $enrollment->academicYear->name_bn ?: ($enrollment->academicYear->name ?? '');
            $tokens['[school_name]'] = $school->name_bn ?: $school->name;
            $tokens['[student_name]'] = $student->student_name_bn ?: $student->student_name_en;
            $tokens['[father_name]'] = $student->father_name_bn ?: $student->father_name;
            $tokens['[mother_name]'] = $student->mother_name_bn ?: $student->mother_name;
            $tokens['[roll_no]'] = $enrollment ? toBengaliNumber($enrollment->roll_no) : '';
            $tokens['[class_name]'] = $enrollment->class->bangla_name ?: ($enrollment->class->name ?? '');
            $tokens['[section_name]'] = $enrollment->section->bangla_name ?: ($enrollment->section->name ?? '');
        }

        return str_replace(array_keys($tokens), array_values($tokens), $content);
    }

    public function print(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'prottayon', 404);
        $setting = DocumentSetting::where('school_id',$school->id)->where('page','prottayon')->first();
        $templateId = $document->data['template_id'] ?? null;
        $template = $templateId ? DocumentTemplate::find($templateId) : null;
        $lang = $template ? $template->language : 'bn';
        return view('principal.documents.prottayon.print', [
            'school'   => $school,
            'document' => $document,
            'student'  => $document->student,
            'setting'  => $setting,
            'template' => $template,
            'lang'     => $lang,
        ]);
    }

    public function edit(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'prottayon', 404);
        $classes   = \App\Models\SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        $sections  = \App\Models\Section::where('school_id', $school->id)->orderBy('name')->get();
        $templates = DocumentTemplate::where('school_id', $school->id)->where('type', 'prottayon')->where('is_active', true)->get();
        return view('principal.documents.prottayon.edit', compact('school','document','classes','sections','templates'));
    }

    public function update(Request $request, School $school, DocumentRecord $document)
    {
        abort_unless($document->school_id === $school->id && $document->type === 'prottayon', 404);
        $validated = $request->validate([
            'class_id'         => 'required|integer',
            'section_id'       => 'nullable|integer',
            'student_id'       => 'required|integer',
            'attestation_type' => 'nullable|string',
            'template_id'      => 'nullable|integer',
            'layout'           => 'nullable|string|in:standard,pad',
            'content'          => 'nullable|string',
            // Student fields for update
            'student_name_bn'  => 'nullable|string',
            'student_name_en'  => 'nullable|string',
            'father_name_bn'   => 'nullable|string',
            'father_name'      => 'nullable|string',
            'mother_name_bn'   => 'nullable|string',
            'mother_name'      => 'nullable|string',
            'date_of_birth'    => 'nullable|date',
            'present_village'  => 'nullable|string',
            'present_post_office' => 'nullable|string',
            'present_upazilla' => 'nullable|string',
            'present_district' => 'nullable|string',
        ]);

        $student = Student::forSchool($school->id)->findOrFail($validated['student_id']);

        // Update student fields if provided
        $studentFields = array_filter(array_intersect_key($validated, array_flip([
            'student_name_bn','student_name_en','father_name_bn','father_name',
            'mother_name_bn','mother_name','date_of_birth',
            'present_village','present_post_office','present_upazilla','present_district',
        ])), fn($v) => $v !== null);
        if (!empty($studentFields)) {
            $student->update($studentFields);
        }

        // Determine final content
        $content = $validated['content'] ?? null;
        if ($content) {
            $parsedContent = $this->parseTemplate($school, $student, $content, $validated['template_id'] ?? null);
        } else {
            $parsedContent = $document->data['custom_content'] ?? '';
        }

        $document->update([
            'student_id' => $student->id,
            'data' => array_merge($document->data ?? [], [
                'attestation_type' => $validated['attestation_type'],
                'class_id'         => $validated['class_id'],
                'section_id'       => $validated['section_id'] ?? null,
                'template_id'      => $validated['template_id'] ?? ($document->data['template_id'] ?? null),
                'layout'           => $validated['layout'] ?? ($document->data['layout'] ?? 'standard'),
                'custom_content'   => $parsedContent,
                'is_final'         => true,
            ]),
        ]);

        return redirect()->route('principal.institute.documents.prottayon.print', [$school, $document->id])
            ->with('success','প্রত্যয়নপত্র হালনাগাদ হয়েছে');
    }
}
