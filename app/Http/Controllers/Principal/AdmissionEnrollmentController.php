<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AdmissionApplication;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdmissionEnrollmentController extends Controller
{
    /**
     * Display list of all applications for enrollment
     */
    public function index(School $school)
    {
        // Get all applications that are accepted and paid, not yet enrolled
        $applications = AdmissionApplication::where('school_id', $school->id)
            ->where('status', 'accepted')
            ->where('payment_status', 'paid')
            ->whereNull('student_id')
            ->with(['academicYear'])
            ->orderBy('class_name')
            ->orderBy('name_bn')
            ->paginate(20);

        return view('principal.admissions.enrollment.index', compact('school', 'applications'));
    }

    /**
     * Show enrollment modal form data
     */
    public function create(School $school, $admission_application)
    {
        try {
            // Manually fetch the application
            $application = AdmissionApplication::findOrFail($admission_application);
            
            // Verify it belongs to this school
            if ($application->school_id !== $school->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'অবৈধ অনুরোধ'
                ], 403);
            }
            
            // Get sections for the application's class
            $classCode = $application->class_name; // stored as class_code in settings
            
            // Resolve SchoolClass either by numeric_value (preferred) or by name fallback
            $schoolClassQuery = SchoolClass::where('school_id', $school->id);
            if (is_numeric($classCode)) {
                $schoolClassQuery->where('numeric_value', intval($classCode));
            } else {
                $schoolClassQuery->where('name', $classCode);
            }
            $schoolClass = $schoolClassQuery->first();

            if (!$schoolClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'ক্লাস খুঁজে পাওয়া যায়নি'
                ], 404);
            }

            $sections = Section::where('school_id', $school->id)
                ->where('class_id', $schoolClass->id)
                ->orderBy('name')
                ->get();

            $groups = Group::where('school_id', $school->id)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'class' => $schoolClass,
                'sections' => $sections,
                'groups' => $groups,
                'application' => $application,
                'requireGroup' => $schoolClass->usesGroups()
            ]);
        } catch (\Exception $e) {
            \Log::error('Enrollment modal data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store enrollment - convert application to student
     */
    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:admission_applications,id',
            'section_id' => 'nullable|exists:sections,id',
            'group_id' => 'nullable|exists:groups,id',
            'roll_no' => 'required|integer|min:1',
        ]);

        $application = AdmissionApplication::findOrFail($validated['application_id']);

        // Verify application belongs to this school
        if ($application->school_id !== $school->id) {
            return back()->with('error', 'অবৈধ অনুরোধ');
        }

        // Check if already enrolled
        if ($application->student_id) {
            return back()->with('error', 'এই শিক্ষার্থী ইতিমধ্যে ভর্তি হয়ে গেছে');
        }

        // Get class info
        $classCode = $application->class_name; // stored code like 6,7,8,9,10
        $schoolClassQuery = SchoolClass::where('school_id', $school->id);
        if (is_numeric($classCode)) {
            $schoolClassQuery->where('numeric_value', intval($classCode));
        } else {
            $schoolClassQuery->where('name', $classCode);
        }
        $schoolClass = $schoolClassQuery->first();

        if (!$schoolClass) {
            return back()->with('error', 'ক্লাস খুঁজে পাওয়া যায়নি');
        }

        // Validate group requirement for class 9 and 10
        if ($schoolClass->usesGroups() && !$validated['group_id']) {
            return back()->with('error', 'ক্লাস ৯ম ও ১০ম এর জন্য গ্রুপ নির্বাচন বাধ্যতামূলক');
        }

        DB::beginTransaction();
        try {
            // Create student record with all data from application
            $student = Student::create([
                'school_id' => $school->id,
                'class_id' => $schoolClass->id,
                'admission_id' => $application->id,
                'student_id' => Student::generateStudentId($school->id, $schoolClass->numeric_value),
                'student_name_en' => $application->name_en,
                'student_name_bn' => $application->name_bn,
                'date_of_birth' => $application->dob,
                'gender' => $application->gender,
                'religion' => $application->religion,
                'father_name' => $application->father_name_en,
                'father_name_bn' => $application->father_name_bn,
                'mother_name' => $application->mother_name_en,
                'mother_name_bn' => $application->mother_name_bn,
                'guardian_name_en' => $application->guardian_name_en,
                'guardian_name_bn' => $application->guardian_name_bn,
                'guardian_phone' => $application->mobile,
                'present_address' => $application->present_address,
                'permanent_address' => $application->permanent_address,
                'present_village' => $application->present_village,
                'present_para_moholla' => $application->present_para_moholla,
                'present_post_office' => $application->present_post_office,
                'present_upazilla' => $application->present_upazilla,
                'present_district' => $application->present_district,
                'permanent_village' => $application->permanent_village,
                'permanent_para_moholla' => $application->permanent_para_moholla,
                'permanent_post_office' => $application->permanent_post_office,
                'permanent_upazilla' => $application->permanent_upazilla,
                'permanent_district' => $application->permanent_district,
                'blood_group' => $application->blood_group,
                'photo' => $application->photo ? $this->copyPhoto($application->photo) : null,
                'admission_date' => now(),
                'status' => 'active',
                'previous_school' => $application->last_school,
                'pass_year' => $application->pass_year,
                'previous_result' => $application->result,
                'previous_remarks' => $application->achievement,
            ]);

            // Create enrollment record
            StudentEnrollment::create([
                'student_id' => $student->id,
                'school_id' => $school->id,
                'academic_year_id' => $application->academic_year_id,
                'class_id' => $schoolClass->id,
                'section_id' => $validated['section_id'],
                'group_id' => $validated['group_id'],
                'roll_no' => $validated['roll_no'],
                'status' => 'active',
            ]);

            // Link application to student
            $application->update(['student_id' => $student->id]);

            DB::commit();

            // Redirect back to enrollment list; subjects can be assigned later (optional)
            return redirect()->route('principal.institute.admissions.enrollment.index', $school)
                ->with('success', 'শিক্ষার্থী সফলভাবে ভর্তি হয়েছে। বিষয় নির্বাচন পরে করা যাবে।');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'ভর্তিতে ত্রুটি: ' . $e->getMessage());
        }
    }

    /**
     * Show subject selection page after enrollment
     */
    public function subjects(School $school, Student $student)
    {
        // Verify student belongs to this school
        if ($student->school_id !== $school->id) {
            abort(404);
        }

        // Get enrollment info
        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('school_id', $school->id)
            ->with(['class', 'section', 'group'])
            ->first();

        if (!$enrollment) {
            return redirect()->route('principal.institute.admissions.enrollment.index', $school)
                ->with('error', 'Enrollment তথ্য পাওয়া যায়নি');
        }

        // Get available subjects for this class
        $availableSubjects = \App\Models\ClassSubject::where('school_id', $school->id)
            ->where('class_id', $enrollment->class_id)
            ->when($enrollment->group_id, function($q) use ($enrollment) {
                $q->where(function($subQ) use ($enrollment) {
                    $subQ->whereNull('group_id')
                        ->orWhere('group_id', $enrollment->group_id);
                });
            })
            ->with('subject')
            ->orderBy('offered_mode')
            ->orderByRaw('COALESCE(order_no, 9999) asc')
            ->get();

        // Get already selected subjects (store by subject_id in student_subjects schema)
        $selectedSubjects = \App\Models\StudentSubject::where('student_enrollment_id', $enrollment->id)
            ->pluck('subject_id')
            ->toArray();

        return view('principal.admissions.enrollment.subjects', compact(
            'school', 
            'student', 
            'enrollment', 
            'availableSubjects', 
            'selectedSubjects'
        ));
    }

    /**
     * Store selected subjects
     */
    public function storeSubjects(Request $request, School $school, Student $student)
    {
        $validated = $request->validate([
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
        ]);

        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('school_id', $school->id)
            ->first();

        if (!$enrollment) {
            return back()->with('error', 'Enrollment তথ্য পাওয়া যায়নি');
        }

        DB::beginTransaction();
        try {
            // Delete existing subjects
            \App\Models\StudentSubject::where('student_enrollment_id', $enrollment->id)->delete();

            // Insert new subjects (save subject_id) if provided
            foreach (($validated['subjects'] ?? []) as $subjectId) {
                \App\Models\StudentSubject::create([
                    'student_enrollment_id' => $enrollment->id,
                    'subject_id' => $subjectId,
                ]);
            }

            DB::commit();

            return redirect()->route('principal.institute.admissions.enrollment.index', $school)
                ->with('success', 'বিষয় নির্বাচন সম্পন্ন হয়েছে। ভর্তি প্রক্রিয়া সম্পূর্ণ!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'বিষয় সংরক্ষণে ত্রুটি: ' . $e->getMessage());
        }
    }



    /**
     * Copy photo from admission folder to students folder
     */
    private function copyPhoto($admissionPhoto)
    {
        if (!$admissionPhoto) return null;

        // Check if photo already in students folder (in case of re-enrollment)
        if (Storage::disk('public')->exists('students/' . $admissionPhoto)) {
            return $admissionPhoto;
        }

        // Source path from admission folder
        $sourcePath = 'admission/' . $admissionPhoto;
        if (!Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        // Generate new filename with timestamp to avoid conflicts
        $extension = pathinfo($admissionPhoto, PATHINFO_EXTENSION);
        $fileName = 'student_' . time() . '_' . uniqid() . '.' . $extension;
        $destPath = 'students/' . $fileName;

        // Copy file from admission to students folder (keeps original in admission)
        Storage::disk('public')->copy($sourcePath, $destPath);

        return $fileName;
    }
}
