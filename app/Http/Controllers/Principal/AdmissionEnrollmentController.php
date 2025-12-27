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
    public function index(School $school, Request $request)
    {
        // Overall dataset for stats (accepted + application-fee paid) regardless of enrollment
        $overallBase = AdmissionApplication::where('school_id', $school->id)
            ->where('status', 'accepted')
            ->where('payment_status', 'paid');

        // Base query for listing (accepted + application-fee paid), includes enrolled and not-yet-enrolled
        $baseQuery = (clone $overallBase)->with(['academicYear','examResults']);

        // Filters
        $permRaw = (string)$request->get('permission', '');
        $perm = ($permRaw === '1' || $permRaw === '0') ? $permRaw : '';
        $filters = [
            'class' => trim((string)$request->get('class', '')),
            'permission' => $perm, // '1','0','' (normalized)
            'fee_status' => $request->get('fee_status', ''), // 'paid','unpaid',''
            'q' => trim((string)$request->get('q', '')),
        ];

        if ($filters['class'] !== '') {
            $baseQuery->where('class_name', $filters['class']);
        }
        if ($filters['permission'] !== '') {
            $baseQuery->where('admission_permission', $filters['permission'] === '1');
        }
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $baseQuery->where(function($sub) use ($q) {
                $sub->where('name_bn','like',"%{$q}%")
                    ->orWhere('name_en','like',"%{$q}%")
                    ->orWhere('app_id','like',"%{$q}%")
                    ->orWhere('mobile','like',"%{$q}%");
            });
        }

        // We need a set of matching IDs for stats and fee filter
        $matchingIds = (clone $baseQuery)->pluck('id');

        // Fee status filter requires payments lookup
        if ($filters['fee_status'] === 'paid' || $filters['fee_status'] === 'unpaid') {
            $paidIds = \App\Models\AdmissionPayment::whereIn('admission_application_id', $matchingIds)
                ->where('status','Completed')
                ->where('fee_type','admission')
                ->pluck('admission_application_id')
                ->unique();
            if ($filters['fee_status'] === 'paid') {
                $baseQuery->whereIn('id', $paidIds);
            } else {
                $baseQuery->whereNotIn('id', $paidIds);
            }
        }

        // Final list with ordering + pagination
        $applications = $baseQuery
            ->orderBy('class_name')
            ->orderBy('name_bn')
            ->paginate(20)
            ->appends($request->query());

        // Attach derived attributes: merit_rank, admission_fee_paid
        // Precompute latest exam per class
        $latestExamByClass = \App\Models\AdmissionExam::where('school_id',$school->id)
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('class_name')
            ->map(function($list){ return $list->first(); });

        // Build merit ranking maps
        $meritMap = [];
        foreach ($latestExamByClass as $className => $exam) {
            $results = \App\Models\AdmissionExamResult::where('exam_id',$exam->id)
                ->orderByDesc('total_obtained')
                ->orderBy('id')
                ->get(['application_id','total_obtained']);
            $rank = 1;
            foreach ($results as $r) {
                $meritMap[$className][$r->application_id] = $rank;
                $rank++;
            }
        }

        // Admission fee paid check by payments table with fee_type='admission' and status 'Completed'
        $applications->getCollection()->transform(function(AdmissionApplication $a) use ($meritMap, $school) {
            $class = (string)($a->class_name ?? '');
            $a->merit_rank = $class && isset($meritMap[$class][$a->id]) ? $meritMap[$class][$a->id] : null;
            $a->admission_fee_paid = \App\Models\AdmissionPayment::where('admission_application_id',$a->id)
                ->where('status','Completed')
                ->when(true, function($q){ $q->where('fee_type','admission'); })
                ->exists();
            return $a;
        });

        // Classes for filter dropdown (from overall dataset)
        $classes = (clone $overallBase)
            ->select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        // Stats for this view (based on current filtered set)
        $visibleIds = $applications->getCollection()->pluck('id');
        // If you want stats for all filtered (not only current page), use $matchingIds
        // Stats should always be based on the overall dataset, not filters
        $overallIds = (clone $overallBase)->pluck('id');
        $permittedCount = (clone $overallBase)->where('admission_permission', true)->count();

        $paidPayments = \App\Models\AdmissionPayment::whereIn('admission_application_id', $overallIds)
            ->where('status','Completed')
            ->where('fee_type','admission')
            ->get(['admission_application_id','amount']);

        $paidIdsAll = $paidPayments->pluck('admission_application_id')->unique();
        $paidCount = $paidIdsAll->count();

        $dueCount = max(0, $permittedCount - $paidCount);

        $totalCollected = (float)$paidPayments->sum('amount');
        $totalAssigned = (float)AdmissionApplication::whereIn('id', $overallIds)
            ->where('admission_permission', true)
            ->sum('admission_fee');
        $totalDue = max(0.0, $totalAssigned - $totalCollected);

        $stats = [
            'permittedCount' => $permittedCount,
            'paidCount' => $paidCount,
            'dueCount' => $dueCount,
            'totalCollected' => $totalCollected,
            'totalAssigned' => $totalAssigned,
            'totalDue' => $totalDue,
        ];

        return view('principal.admissions.enrollment.index', compact('school', 'applications','filters','classes','stats'));
    }

    /**
     * Print view for enrollment list (approved/unapproved based on filters)
     */
    public function print(School $school, Request $request)
    {
        // Reuse the same filtering logic as index but without pagination
        $overallBase = AdmissionApplication::where('school_id', $school->id)
            ->where('status', 'accepted')
            ->where('payment_status', 'paid');

        $baseQuery = (clone $overallBase)->with(['academicYear','examResults']);

        $filters = [
            'class' => trim((string)$request->get('class', '')),
            'permission' => $request->get('permission', ''), // '1','0',''
            'fee_status' => $request->get('fee_status', ''), // 'paid','unpaid',''
            'q' => trim((string)$request->get('q', '')),
        ];

        if ($filters['class'] !== '') { $baseQuery->where('class_name', $filters['class']); }
        if ($filters['permission'] !== '') { $baseQuery->where('admission_permission', $filters['permission'] === '1'); }
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $baseQuery->where(function($sub) use ($q) {
                $sub->where('name_bn','like',"%{$q}%")
                    ->orWhere('name_en','like',"%{$q}%")
                    ->orWhere('app_id','like',"%{$q}%")
                    ->orWhere('mobile','like',"%{$q}%");
            });
        }

        $matchingIds = (clone $baseQuery)->pluck('id');
        if ($filters['fee_status'] === 'paid' || $filters['fee_status'] === 'unpaid') {
            $paidIds = \App\Models\AdmissionPayment::whereIn('admission_application_id', $matchingIds)
                ->where('status','Completed')
                ->where('fee_type','admission')
                ->pluck('admission_application_id')
                ->unique();
            if ($filters['fee_status'] === 'paid') { $baseQuery->whereIn('id', $paidIds); }
            else { $baseQuery->whereNotIn('id', $paidIds); }
        }

        $applications = $baseQuery
            ->orderBy('class_name')
            ->orderBy('name_bn')
            ->get();

        // Attach merit rank and fee paid flags
        $latestExamByClass = \App\Models\AdmissionExam::where('school_id',$school->id)
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('class_name')
            ->map(function($list){ return $list->first(); });

        $meritMap = [];
        foreach ($latestExamByClass as $className => $exam) {
            $results = \App\Models\AdmissionExamResult::where('exam_id',$exam->id)
                ->orderByDesc('total_obtained')
                ->orderBy('id')
                ->get(['application_id','total_obtained']);
            $rank = 1; foreach ($results as $r) { $meritMap[$className][$r->application_id] = $rank; $rank++; }
        }

        $applications->transform(function(AdmissionApplication $a) use ($meritMap) {
            $class = (string)($a->class_name ?? '');
            $a->merit_rank = $class && isset($meritMap[$class][$a->id]) ? $meritMap[$class][$a->id] : null;
            $a->admission_fee_paid = \App\Models\AdmissionPayment::where('admission_application_id',$a->id)
                ->where('status','Completed')
                ->when(true, function($q){ $q->where('fee_type','admission'); })
                ->exists();
            return $a;
        });

        // Sorting options: class, roll, merit
        $sortBy = $request->get('sort', 'class'); // class|roll|merit
        $order = strtolower((string)$request->get('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sorters = [
            'class' => function(AdmissionApplication $a){ return $a->class_name ?? ''; },
            'roll' => function(AdmissionApplication $a){ return $a->admission_roll_no ?? 0; },
            'merit' => function(AdmissionApplication $a){ return $a->merit_rank ?? PHP_INT_MAX; },
        ];
        if(isset($sorters[$sortBy])){
            $applications = $order === 'desc' ? $applications->sortByDesc($sorters[$sortBy])->values() : $applications->sortBy($sorters[$sortBy])->values();
        }

        // Classes for filter dropdown
        $classes = (clone $overallBase)
            ->select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return view('principal.admissions.enrollment.print', compact('school','applications','filters','classes'));
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
     * Permission data endpoint for modal prefill
     */
    public function permissionData(School $school, AdmissionApplication $application)
    {
        if ($application->school_id !== $school->id) {
            return response()->json(['message'=>'Invalid request'], 403);
        }
        return response()->json([
            'permission' => (bool)($application->admission_permission ?? false),
            'admission_fee' => (float)($application->admission_fee ?? 0),
            'paid' => \App\Models\AdmissionPayment::where('admission_application_id',$application->id)
                ->where('status','Completed')
                ->where('fee_type','admission')
                ->exists(),
        ]);
    }

    /**
     * Store permission + admission fee
     */
    public function permissionStore(Request $request, School $school)
    {
        $data = $request->validate([
            'application_id' => 'required|exists:admission_applications,id',
            'permission' => 'required|in:0,1',
            'admission_fee' => 'required|numeric|min:0',
        ]);
        $application = AdmissionApplication::findOrFail($data['application_id']);
        if ($application->school_id !== $school->id) {
            return back()->with('error','অবৈধ অনুরোধ');
        }
        // Block editing permission/fee after admission fee is paid
        $admissionFeePaid = \App\Models\AdmissionPayment::where('admission_application_id',$application->id)
            ->where('status','Completed')
            ->where('fee_type','admission')
            ->exists();
        if ($admissionFeePaid) {
            return back()->with('error','ভর্তি ফিস পরিশোধিত হওয়ায় অনুমতি পরিবর্তন করা যাবে না');
        }
        $application->admission_permission = (bool)$data['permission'];
        $application->admission_fee = (float)$data['admission_fee'];
        $application->save();

        return back()->with('success','ভর্তি অনুমতি ও ফিস নির্ধারণ সংরক্ষিত হয়েছে');
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
