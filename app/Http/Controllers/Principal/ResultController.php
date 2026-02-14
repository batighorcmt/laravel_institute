<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Result;
use App\Models\School;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Mark;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\StudentEnrollment;
use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    // Marksheet
    public function marksheet(Request $request, School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $academicYears = AcademicYear::forSchool($school->id)->orderBy('start_date', 'desc')->get();
        
        $results = collect();
        $finalSubjects = collect();
        $marks = collect();
        $examSubjects = collect();
        $exam = null; $class = null; 
        
        $sections = collect();
        $exams = collect();

        if ($request->filled('exam_id') && $request->filled('class_id')) {
            $calcData = $this->getCalculatedResults($school, $request->exam_id, $request->class_id, $request->section_id, $request->student_id);
            
            if ($calcData) {
                $results = $calcData['results'];
                $finalSubjects = $calcData['finalSubjects'];
                $exam = $calcData['exam'];
                $class = $calcData['class'];
                $examSubjects = $calcData['examSubjects'];
            }
            
            // Populate dropdowns for view
            $sections = Section::forSchool($school->id)->where('class_id', $request->class_id)->ordered()->get();
            $exams = Exam::forSchool($school->id)->forAcademicYear($request->academic_year_id)->orderBy('created_at', 'desc')->get();
        } else {
             // Initial load empty or partial
             if ($request->filled('academic_year_id')) {
                $exams = Exam::forSchool($school->id)->forAcademicYear($request->academic_year_id)->orderBy('created_at', 'desc')->get();
             }
        }
        
        // Handle Print All
        if ($request->filled('print_all') && $results->count() > 0) {
             return view('principal.results.print-all-marksheets', compact('school', 'exam', 'class', 'results', 'finalSubjects'));
        }

        return view('principal.results.marksheet', compact('school', 'classes', 'academicYears', 'sections', 'exams', 'results', 'exam', 'class', 'finalSubjects'));
    }

    // Print individual marksheet
    public function printMarksheet(School $school, Exam $exam, Student $student)
    {
        // Use helper to get calculated result for this student
        // We need class_id. Exam usually has class_id, or we get it from student
        $classId = $exam->class_id; 
        if (!$classId) {
             // Fallback if exam doesn't have class_id? unique exams are usually per class.
             // If not, we might need to find it. 
             $enrollment = $student->enrollments()->where('academic_year_id', $exam->academic_year_id)->first();
             $classId = $enrollment ? $enrollment->class_id : null;
        }

        if (!$classId) abort(404, 'Class not found for student');

        $calcData = $this->getCalculatedResults($school, $exam->id, $classId, null, $student->id);
        
        if (!$calcData || $calcData['results']->isEmpty()) {
             // If no result computed (e.g. absent or strictly no record), we might still want to print "Not Found" or empty
             // But usually we want to show what we have.
             return back()->with('error', 'Result not calculated or found.');
        }

        $result = $calcData['results']->first();
        $finalSubjects = $calcData['finalSubjects'];
        
        // We don't need $marks separately because result has subject_results
        
        return view('principal.results.print-marksheet', compact('school', 'exam', 'student', 'result', 'finalSubjects'));
    }

    // Merit List
    public function meritList(Request $request, School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->orderBy('created_at', 'desc')->get();

        $results = null;
        $exam = null;
        $class = null;

        if ($request->has('exam_id') && $request->has('class_id')) {
            $exam = Exam::find($request->exam_id);
            $class = SchoolClass::find($request->class_id);

            $results = Result::with(['student'])
                ->forExam($request->exam_id)
                ->forClass($request->class_id)
                ->passed()
                ->orderByMerit()
                ->get();

            // Update merit positions
            $position = 1;
            foreach ($results as $result) {
                $result->update(['merit_position' => $position++]);
            }
        }

        return view('principal.results.merit-list', compact('school', 'classes', 'exams', 'results', 'exam', 'class'));
    }

    // Print Merit List
    public function printMeritList(School $school, Exam $exam, $classId)
    {
        $class = SchoolClass::find($classId);

        $results = Result::with(['student'])
            ->forExam($exam->id)
            ->forClass($classId)
            ->passed()
            ->orderByMerit()
            ->get();

        return view('principal.results.print-merit-list', compact('school', 'exam', 'class', 'results'));
    }

    // Tabulation Sheet
    public function tabulation(Request $request, School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $academicYears = AcademicYear::forSchool($school->id)->orderBy('start_date', 'desc')->get();
        $sections = collect();
        // initially exams are empty; will be filtered by academic year when requested
        $exams = collect();

        $students = null;
        $exam = null;
        $class = null;
        $examSubjects = null;
        $classSubjects = collect();
        $finalSubjects = collect(); // Combined subjects for display
        $results = collect();
        $marks = collect();

        if ($request->filled('exam_id') && $request->filled('class_id')) {
            $exam = Exam::with('examSubjects.subject')->find($request->exam_id);
            // Safety: If exam is linked to a class, always use THAT class_id
            $classId = ($exam && $exam->class_id) ? $exam->class_id : $request->class_id;
            $class = SchoolClass::find($classId);
            
            $examSubjects = $exam ? $exam->examSubjects()->orderBy('display_order')->get() : collect();
            $examSubjectIds = $examSubjects->pluck('subject_id')->toArray();
            // Fetch Class Subjects with Group info for sorting
            $classSubjects = ClassSubject::where('class_id', $classId)
                ->whereIn('subject_id', $examSubjectIds)
                ->with('group')
                ->get()
                ->keyBy('subject_id');

            // ... (Sorting logic remains same) ...
            $examSubjects = $examSubjects->sort(function($a, $b) use ($classSubjects) {
                $csA = $classSubjects[$a->subject_id] ?? null;
                $csB = $classSubjects[$b->subject_id] ?? null;
                
                // Group Priority
                $groupA = $csA ? strtolower($csA->group->name ?? '') : '';
                $groupB = $csB ? strtolower($csB->group->name ?? '') : '';
                
                // Define priority map (lower is first)
                $priority = function($gName) {
                    if (empty($gName)) return 0; // Common
                    if (str_contains($gName, 'science') || str_contains($gName, 'বিজ্ঞান')) return 1;
                    if (str_contains($gName, 'humanities') || str_contains($gName, 'মানবিক')) return 2;
                    if (str_contains($gName, 'business') || str_contains($gName, 'ব্যবসায়')) return 3;
                    return 4; // Other
                };
                
                $pA = $priority($groupA);
                $pB = $priority($groupB);
                
                if ($pA != $pB) return $pA <=> $pB;
                
                // Optional Priority
                $optA = $csA ? $csA->is_optional : 0;
                $optB = $csB ? $csB->is_optional : 0;
                
                if ($optA != $optB) return $optA <=> $optB;
                
                // Order No Priority
                $orderA = $csA ? $csA->order_no : 999;
                $orderB = $csB ? $csB->order_no : 999;
                
                return $orderA <=> $orderB;
            });

            // Load sections for the selected class (for the section dropdown)
            $sections = Section::forSchool($school->id)->where('class_id', $classId)->ordered()->get();

            // Results for the selected class/exam, optionally filtered by section
            $resultQuery = Result::with(['student'])
                ->forExam($exam->id)
                ->forClass($classId)
                ->orderByMerit();

            if ($request->filled('section_id')) {
                $resultQuery->where('results.section_id', $request->section_id);
            }

            $results = $resultQuery->get();

            // Fetch students who are enrolled in this class/section for THIS academic year
            $enrollmentQuery = StudentEnrollment::where('school_id', $school->id)
                ->where('class_id', $classId)
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('status','active');
                
            if ($request->filled('section_id')) {
                $enrollmentQuery->where('section_id', $request->section_id);
            }
            
            $enrolledStudentIds = $enrollmentQuery->pluck('student_id')->unique()->values()->all();

            // Union all student ids we want to show (Results + Enrolled)
            $allStudentIds = collect($results->pluck('student_id'))
                ->merge($enrolledStudentIds)
                ->unique()
                ->values()
                ->all();

            // Fetch marks for all these students (keyed by student and exam_subject)
            $marks = !empty($allStudentIds) ? Mark::forExam($exam->id)->whereIn('student_id', $allStudentIds)->get() : collect();

            // Only include students who are active in students table
            $activeStudentIds = Student::whereIn('id', $allStudentIds)->where('status','active')->pluck('id')->unique()->values()->all();

            // Pre-load assigned subjects for all these students to check applicability and filter
            $allEnrollmentIds = StudentEnrollment::whereIn('student_id', $activeStudentIds)
                ->where('academic_year_id', $exam->academic_year_id)
                ->pluck('id');
            
            $assignedSubjectsMap = StudentSubject::with('enrollment')->whereIn('student_enrollment_id', $allEnrollmentIds)
                ->get()
                ->groupBy(function($item) {
                    return $item->enrollment->student_id;
                });
            
            $studentAssignedSubjectIds = $assignedSubjectsMap->map(function($items) {
                return $items->pluck('subject_id')->unique()->toArray();
            });

            // Requirement: If student has NO subjects assigned in this exam, do not show them.
            $activeStudentIds = array_filter($activeStudentIds, function($sid) use ($studentAssignedSubjectIds, $examSubjectIds) {
                $assigned = $studentAssignedSubjectIds[$sid] ?? [];
                return !empty(array_intersect($assigned, $examSubjectIds));
            });

            // Build a results collection that includes an item for each (active) student we want to display.
            // If a persistent Result exists use it; otherwise create a lightweight placeholder with student relation.
            $resultsCollection = collect();
            foreach ($activeStudentIds as $sid) {
                $existing = Result::forExam($exam->id)->forStudent($sid)->first();
                $stu = Student::with(['currentEnrollment.section','currentEnrollment.group'])->find($sid);
                if ($existing) {
                    // attach enrollment/section/group info for sorting/display
                    $existing->student = $stu;
                    $existing->group_name = optional($stu->currentEnrollment->group)->name ?? null;
                } else {
                    $fake = new Result();
                    $fake->student_id = $sid;
                    $fake->student = $stu;
                    $fake->total_marks = 0;
                    $fake->gpa = 0;
                    $fake->letter_grade = null;
                    $fake->result_status = 'not_computed';
                    $fake->group_name = optional($stu->currentEnrollment->group)->name ?? null;
                    $existing = $fake;
                }

                // determine optional subject code (if any) from student's enrollment subjects
                $optionalCode = null;
                $optionalId = null;
                if ($stu && $stu->currentEnrollment) {
                    $optionalSubject = StudentSubject::where('student_enrollment_id', $stu->currentEnrollment->id)
                        ->where('is_optional', true)
                        ->with(['subject'])
                        ->first();

                    if ($optionalSubject) {
                        $optionalCode = optional($optionalSubject->subject)->code ?? null;
                        $optionalId = optional($optionalSubject->subject)->id ?? null;
                    }
                }

                $existing->fourth_subject_code = $optionalCode;
                $existing->fourth_subject_id = $optionalId;
                $existing->assigned_subject_ids = $studentAssignedSubjectIds[$sid] ?? []; // Attach for later use
                $resultsCollection->push($existing);
            }

            // 2b. Identify combined groups and prepare final subject list for columns
            $finalSubjects = collect();
            $processedGroups = [];

            foreach ($examSubjects as $sub) {
                if ($sub->combine_group) {
                    $groupName = trim($sub->combine_group);
                    
                    // Add individual subject as 'display only'
                    $keyInd = 'ind_'.$sub->id;
                    $finalSubjects->put($keyInd, [
                        'type' => 'individual', // Display marks only
                        'subject_id' => $sub->subject_id,
                        'name' => $sub->subject->name,
                        'code' => $sub->subject->code,
                        'creative_full_mark' => $sub->creative_full_mark,
                        'mcq_full_mark' => $sub->mcq_full_mark,
                        'practical_full_mark' => $sub->practical_full_mark,
                        'total_full_mark' => $sub->total_full_mark,
                        'display_only' => true, // Flag for view
                        'component_ids' => [$sub->id] // Points to itself for mark fetching
                    ]);

                    // Check if we need to add the Combined Subject (only once per group, ideally after the last one of the group?)
                    // The user wants: "First show individual subjects... THEN merged subject".
                    // So we should add the combined subject AFTER the LAST individual subject of that group.
                    // We need to know if this is the last subject of this group traversing in order.
                    // Let's check remaining subjects in examSubjects.
                    $remainingInGroup = $examSubjects->where('combine_group', $groupName)->where('id', '>', $sub->id)->count();
                    
                    if ($remainingInGroup == 0 && !in_array($groupName, $processedGroups)) {
                        // This is the last one, so add the Combined Virtual Subject
                        $allInGroup = $examSubjects->where('combine_group', $groupName);
                        
                        $groupTotalFull = $allInGroup->sum('total_full_mark');
                        $groupPassFull = $allInGroup->sum(function($s){
                            return $s->creative_pass_mark + $s->mcq_pass_mark + $s->practical_pass_mark;
                        });

                        $compIds = $allInGroup->pluck('id')->toArray();
                        
                        $keyComb = 'comb_'.md5($groupName);
                        
                        $finalSubjects->put($keyComb, [
                            'type' => 'combined',
                            'subject_id' => null, // Virtual
                            'name' => $groupName,
                            'code' => '',
                            'creative_full_mark' => $allInGroup->sum('creative_full_mark'),
                            'mcq_full_mark' => $allInGroup->sum('mcq_full_mark'),
                            'practical_full_mark' => $allInGroup->sum('practical_full_mark'),
                            'total_full_mark' => $groupTotalFull,
                            'total_pass_mark' => $groupPassFull,
                            'pass_type' => 'combined', 
                            'component_ids' => $compIds,
                            'is_combined_result' => true
                        ]);
                        $processedGroups[] = $groupName;
                    }

                } else {
                    $key = 's_'.$sub->id;
                    $finalSubjects->put($key, [
                        'type' => 'single',
                        'subject_id' => $sub->subject_id,
                        'name' => $sub->subject->name,
                        'code' => $sub->subject->code ?? '',
                        'creative_full_mark' => $sub->creative_full_mark,
                        'mcq_full_mark' => $sub->mcq_full_mark,
                        'practical_full_mark' => $sub->practical_full_mark,
                        'total_full_mark' => $sub->total_full_mark,
                        'creative_pass_mark' => $sub->creative_pass_mark,
                        'mcq_pass_mark' => $sub->mcq_pass_mark,
                        'practical_pass_mark' => $sub->practical_pass_mark,
                        'total_pass_mark' => ($sub->creative_pass_mark + $sub->mcq_pass_mark + $sub->practical_pass_mark),
                        'pass_type' => $sub->pass_type,
                        'component_ids' => [$sub->id]
                    ]);
                }
            }

            // Now compute per-student totals
            foreach ($resultsCollection as $res) {
                $sid = $res->student_id;
                $studentMarks = $marks->where('student_id', $sid);
                
                $grandTotal = 0;
                $totalGpa = 0;
                $subjectCount = 0;
                $failedSubjectCount = 0;
                $hasAbsent = false;

                $res->subject_results = collect(); // Store processed result for each final subject

                // Determine Student Group
                $studentGroupId = $stu->currentEnrollment ? $stu->currentEnrollment->group_id : null;
                $currentStudentOptionalId = $res->fourth_subject_id;
                $assignedSubIds = $res->assigned_subject_ids ?? [];

                foreach ($finalSubjects as $key => $fSub) {
                    // --- APPLICABILITY CHECK START ---
                    $isApplicable = false;
                    $subjId = $fSub['subject_id'] ?? null;
                    
                    if ($subjId) {
                        // Check if this subject_id is assigned to the student
                        $isApplicable = in_array($subjId, $assignedSubIds);
                    } else if (!empty($fSub['component_ids'])) {
                        // Merged subject: check if ANY component subject is assigned
                        foreach ($fSub['component_ids'] as $cid) {
                            $comp = $examSubjects->firstWhere('id', $cid);
                            if ($comp && in_array($comp->subject_id, $assignedSubIds)) {
                                $isApplicable = true;
                                break;
                            }
                        }
                    }

                    // --- SECOND CHANCE: If it has marks, it is applicable! ---
                    if (!$isApplicable) {
                         $hasAnyMark = false;
                         foreach ($fSub['component_ids'] as $eid) {
                             if ($studentMarks->firstWhere('exam_subject_id', $eid)) { $hasAnyMark = true; break; }
                         }
                         if ($hasAnyMark) $isApplicable = true;
                    }
                    
                    if (!$isApplicable) {
                         $res->subject_results->put($key, [
                            'grade' => '', 
                            'gpa' => '', 
                            'total' => '',
                            'display_only' => true, // Treat as display only (empty)
                            'is_not_applicable' => true // Custom flag for view
                        ]);
                        continue; // Skip processing and fail counting
                    }
                    // --- APPLICABILITY CHECK END ---

                    // Check if this is the optional subject
                     $isOptional = false;
                     foreach ($fSub['component_ids'] as $cid) {
                         $comp = $examSubjects->firstWhere('id', $cid);
                         if ($comp && $comp->subject_id == $currentStudentOptionalId) {
                             $isOptional = true;
                             break;
                         }
                     }

                    // Check if this subject is DISPLAY ONLY (Individual part of a merged group)
                    if (!empty($fSub['display_only'])) {
                        // Just aggregate marks for display
                        $subTotal = 0;
                        $subCreative = 0;
                        $subMcq = 0;
                        $subPractical = 0;
                        $isAbsent = false;
                        $hasRecord = false;
                        
                        foreach ($fSub['component_ids'] as $eid) {
                            $m = $studentMarks->firstWhere('exam_subject_id', $eid);
                            if ($m) {
                                $hasRecord = true;
                                if ($m->is_absent) $isAbsent = true;
                                
                                $subTotal += $m->total_marks;
                                $subCreative += $m->creative_marks;
                                $subMcq += $m->mcq_marks;
                                $subPractical += $m->practical_marks;
                            }
                        }
                        
                        if (!$hasRecord) $subGrade = 'N/R';
                        elseif ($isAbsent) $subGrade = 'F'; 
                        else $subGrade = ''; 

                        $res->subject_results->put($key, [
                            'grade' => $subGrade,
                            'gpa' => 0, 
                            'total' => $subTotal,
                            'creative' => $subCreative,
                            'mcq' => $subMcq,
                            'practical' => $subPractical,
                            'is_optional' => false,
                            'is_absent' => $isAbsent,
                            'display_only' => true
                        ]);
                        continue; 
                    }

                    // Standard Calculation for Single or Combined Virtual Subject
                    $subTotal = 0;
                    $subCreative = 0;
                    $subMcq = 0;
                    $subPractical = 0;
                    
                    $totalCreativePass = 0;
                    $totalMcqPass = 0;
                    $totalPracticalPass = 0;
                    $groupHasEachPassType = false;

                    $subGP = 0;
                    $subGrade = '';
                    $isAbsent = true; 
                    $hasRecord = false;
                    $isFailed = false;

                    foreach ($fSub['component_ids'] as $eid) {
                        $m = $studentMarks->firstWhere('exam_subject_id', $eid);
                        $subComp = $examSubjects->firstWhere('id', $eid); 
                        
                        if ($subComp) {
                            $totalCreativePass += $subComp->creative_pass_mark;
                            $totalMcqPass += $subComp->mcq_pass_mark;
                            $totalPracticalPass += $subComp->practical_pass_mark;
                            if ($subComp->pass_type === 'each') {
                                $groupHasEachPassType = true;
                            }
                        }

                        if ($m) {
                            $hasRecord = true;
                            if (!$m->is_absent) {
                                $isAbsent = false;
                            }
                            $subTotal += $m->total_marks;
                            $subCreative += $m->creative_marks;
                            $subMcq += $m->mcq_marks;
                            $subPractical += $m->practical_marks;
                        }
                    }

                    if ($groupHasEachPassType) {
                        if ($totalCreativePass > 0 && $subCreative < $totalCreativePass) $isFailed = true;
                        if ($totalMcqPass > 0 && $subMcq < $totalMcqPass) $isFailed = true;
                        if ($totalPracticalPass > 0 && $subPractical < $totalPracticalPass) $isFailed = true;
                    }

                    if (!$hasRecord) {
                        $subGrade = 'N/R';
                        $subGP = 0.00;
                    } elseif ($isAbsent) {
                        $subGrade = 'F'; 
                        $subGP = 0.00;   
                        $hasAbsent = true;
                        $isFailed = true; 
                    } else {
                        $percent = ($fSub['total_full_mark'] > 0) ? ($subTotal / $fSub['total_full_mark']) * 100 : 0;
                        if ($isFailed || $subTotal < $fSub['total_pass_mark']) {
                            $subGrade = 'F';
                            $subGP = 0.00;
                        } else {
                            if ($percent >= 80) { $subGrade = 'A+'; $subGP = 5.00; }
                            elseif ($percent >= 70) { $subGrade = 'A'; $subGP = 4.00; }
                            elseif ($percent >= 60) { $subGrade = 'A-'; $subGP = 3.50; }
                            elseif ($percent >= 50) { $subGrade = 'B'; $subGP = 3.00; }
                            elseif ($percent >= 40) { $subGrade = 'C'; $subGP = 2.00; }
                            elseif ($percent >= 33) { $subGrade = 'D'; $subGP = 1.00; }
                            else { $subGrade = 'F'; $subGP = 0.00; }
                        }
                    }

                    $res->subject_results->put($key, [
                        'grade' => $subGrade,
                        'gpa' => $subGP,
                        'total' => $subTotal, 
                        'creative' => $subCreative,
                        'mcq' => $subMcq,
                        'practical' => $subPractical,
                        'is_optional' => $isOptional,
                        'is_absent' => $isAbsent
                    ]);

                    if ($isOptional) {
                        if ($subGP > 2.00) {
                            $bonus = $subGP - 2.00;
                            $totalGpa += $bonus;
                        }
                    } else {
                        $grandTotal += $subTotal;
                        $totalGpa += $subGP;
                        $subjectCount++; // Dynamic count of compulsory applicable subjects
                        
                        if ($subGrade == 'F' || $subGrade == 'N/R') {
                            $failedSubjectCount++;
                        }
                    }
                }

                // Final Calculation
                $divisor = $exam->total_subjects_without_fourth > 0 
                    ? min($exam->total_subjects_without_fourth, $subjectCount)
                    : $subjectCount;

                $finalGpa = ($divisor > 0) ? round($totalGpa / $divisor, 2) : 0.00;
                if ($finalGpa > 5.00) $finalGpa = 5.00;

                $finalLetter = '';
                if ($failedSubjectCount > 0) {
                    $finalLetter = 'F';
                    $finalGpa = 0.00; 
                } elseif ($finalGpa >= 5.00) $finalLetter = 'A+';
                elseif ($finalGpa >= 4.00) $finalLetter = 'A';
                elseif ($finalGpa >= 3.50) $finalLetter = 'A-';
                elseif ($finalGpa >= 3.00) $finalLetter = 'B';
                elseif ($finalGpa >= 2.00) $finalLetter = 'C';
                elseif ($finalGpa >= 1.00) $finalLetter = 'D';
                else $finalLetter = 'F';

                $res->computed_total_marks = $grandTotal;
                $res->computed_gpa = $finalGpa;
                $res->computed_letter = $finalLetter;
                $res->computed_status = ($finalLetter == 'F') ? 'অকৃতকার্য' : 'উত্তীর্ণ';
                $res->fail_count = $failedSubjectCount;
            }

            // Sort results by section numeric value, then by roll number
            $results = $resultsCollection->sortBy(function($r){
                // Section order: Try to get numeric order from section object if available, else name
                $section = optional(optional($r->student)->currentEnrollment)->section;
                $secOrder = $section ? ($section->numeric_value ?? $section->id) : 999999;
                
                $roll = optional(optional($r->student)->currentEnrollment)->roll_no ?? 999999;
                
                // Return a string that sorts naturally: padded section order + padded roll
                return sprintf('%09d-%09d', $secOrder, $roll);
            })->values();

            // Keep a lightweight students collection for any other display needs
            $students = !empty($allStudentIds) ? Student::whereIn('id', $allStudentIds)->get() : collect();
        } else {
            // If academic_year_id is provided, load exams for that year for initial dropdown population
            if ($request->filled('academic_year_id')) {
                $exams = Exam::forSchool($school->id)->forAcademicYear($request->academic_year_id)->orderBy('created_at','desc')->get();
            }
        }

        return view('principal.results.tabulation', compact('school', 'classes', 'academicYears', 'sections', 'exams', 'students', 'exam', 'class', 'examSubjects', 'classSubjects', 'finalSubjects', 'results', 'marks'));
    }



    // AJAX: get exams for an academic year and class (used by tabulation cascading select)
    public function examsByYear(Request $request, School $school)
    {
        $yearId = $request->get('academic_year_id');
        $classId = $request->get('class_id');
        
        if (! $yearId) {
            return response()->json([], 200);
        }

        $query = Exam::forSchool($school->id)->forAcademicYear($yearId);
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        $exams = $query->orderBy('created_at','desc')->get(['id','name']);
        return response()->json($exams);
    }

    // AJAX: get sections for a class (used by tabulation cascading select)
    public function sectionsByClass(Request $request, School $school)
    {
        $classId = $request->get('class_id');
        if (! $classId) {
            return response()->json([], 200);
        }

        $sections = Section::forSchool($school->id)->where('class_id', $classId)->ordered()->get(['id','name']);
        return response()->json($sections);
    }

    // Statistics
    public function statistics(Request $request, School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->orderBy('created_at', 'desc')->get();

        $stats = null;
        $exam = null;
        $class = null;

        if ($request->has('exam_id') && $request->has('class_id')) {
            $exam = Exam::find($request->exam_id);
            $class = SchoolClass::find($request->class_id);

            $stats = $this->calculateStatistics($exam->id, $class->id);
        }

        return view('principal.results.statistics', compact('school', 'classes', 'exams', 'stats', 'exam', 'class'));
    }

    private function calculateStatistics($examId, $classId)
    {
        $results = Result::forExam($examId)->forClass($classId)->get();

        $totalStudents = $results->count();
        $passedStudents = $results->where('result_status', 'pass')->count();
        $failedStudents = $results->where('result_status', 'fail')->count();

        $passRate = $totalStudents > 0 ? round(($passedStudents / $totalStudents) * 100, 2) : 0;

        $gradeDistribution = [
            'A+' => $results->where('letter_grade', 'A+')->count(),
            'A' => $results->where('letter_grade', 'A')->count(),
            'A-' => $results->where('letter_grade', 'A-')->count(),
            'B' => $results->where('letter_grade', 'B')->count(),
            'C' => $results->where('letter_grade', 'C')->count(),
            'D' => $results->where('letter_grade', 'D')->count(),
            'F' => $results->where('letter_grade', 'F')->count(),
        ];

        $gpaStats = [
            'highest' => $results->max('gpa'),
            'lowest' => $results->where('result_status', 'pass')->min('gpa'),
            'average' => round($results->where('result_status', 'pass')->avg('gpa'), 2),
        ];

        return [
            'total_students' => $totalStudents,
            'passed_students' => $passedStudents,
            'failed_students' => $failedStudents,
            'pass_rate' => $passRate,
            'grade_distribution' => $gradeDistribution,
            'gpa_stats' => $gpaStats,
        ];
    }

    // Publish Results
    public function publishResults(School $school, Exam $exam)
    {
        Result::forExam($exam->id)->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return back()->with('success', 'ফলাফল সফলভাবে প্রকাশ করা হয়েছে');
    }

    // Unpublish Results
    public function unpublishResults(School $school, Exam $exam)
    {
        Result::forExam($exam->id)->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function unpublishResult(Request $request, School $school, $examId)
    {
        $exam = Exam::findOrFail($examId);
        $exam->update(['status' => 'active']); // Revert to active or calculate again? Or just hide?
        // Usually unpublish means set published_at to null or similar.
        // Assuming status 'active' means not published/completed.
        // Or if there is a 'published' status.
        // Let's assume 'active'.
        
        return back()->with('success', 'ফলাফল সফলভাবে আনপাবলিশ করা হয়েছে');
    }

    // Print Tabulation Sheet
    public function printTabulation(Request $request, School $school, $examId, $classId)
    {
        // Re-use logic from tabulation, but focused on specific exam/class
        // Simulate request parameters
        // ... (rest of method same as before but inside class) ...
        $examId = $request->exam_id ?: $examId;
        $classId = $request->class_id ?: $classId;
        $sectionId = $request->section_id;
        $lang = $request->get('lang', 'bn');
        
        // Call internal or private method? 
        // Or better: Just copy-paste logic? Copy headers is safer for now to avoid refactoring risk.
        // Wait, I can just include the logic here.
        
        $academicYears = AcademicYear::forSchool($school->id)->get();
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->orderBy('created_at', 'desc')->get();
        $sections = \App\Models\Section::forSchool($school->id)->where('class_id', $classId)->get();

        $exam = Exam::with(['examSubjects.subject'])->find($examId);
        // Safety: If exam is linked to a class, always use THAT class_id
        $classId = ($exam && $exam->class_id) ? $exam->class_id : $classId;
        $class = SchoolClass::find($classId);
        
        // --- CORE TABULATION LOGIC COPY START ---
        // (Simplified version of what is in tabulation method)
        
        $results = collect();
        $finalSubjects = collect();

        if ($exam && $class) {
            // 1. Get Exam Subjects
            $examSubjects = $exam->examSubjects()->with('subject')->get();
            
            // Fetch Class Subjects with Group info for sorting
            $classSubjects = ClassSubject::where('class_id', $classId)
                ->whereIn('subject_id', $examSubjects->pluck('subject_id'))
                ->with('group')
                ->get()
                ->keyBy('subject_id');

            // ... (Sorting logic remains same) ...
            $examSubjects = $examSubjects->sort(function($a, $b) use ($classSubjects) {
                $csA = $classSubjects[$a->subject_id] ?? null;
                $csB = $classSubjects[$b->subject_id] ?? null;
                
                // Group Priority
                $groupA = $csA ? strtolower($csA->group->name ?? '') : '';
                $groupB = $csB ? strtolower($csB->group->name ?? '') : '';
                
                // Define priority map (lower is first)
                $priority = function($gName) {
                    if (empty($gName)) return 0; // Common
                    if (str_contains($gName, 'science') || str_contains($gName, 'বিজ্ঞান')) return 1;
                    if (str_contains($gName, 'humanities') || str_contains($gName, 'মানবিক')) return 2;
                    if (str_contains($gName, 'business') || str_contains($gName, 'ব্যবসায়')) return 3;
                    return 4; // Other
                };
                
                $pA = $priority($groupA);
                $pB = $priority($groupB);
                
                if ($pA != $pB) return $pA <=> $pB;
                
                // Optional Priority
                $optA = $csA ? $csA->is_optional : 0;
                $optB = $csB ? $csB->is_optional : 0;
                
                if ($optA != $optB) return $optA <=> $optB;
                
                // Order No Priority
                $orderA = $csA ? $csA->order_no : 999;
                $orderB = $csB ? $csB->order_no : 999;
                
                return $orderA <=> $orderB;
            });

            // 2. Prepare Display Columns (Group merged subjects)
            // Logic to merge subjects based on combine_group
            $processedGroups = [];
            foreach ($examSubjects as $sub) {
                if ($sub->combine_group) {
                    if (in_array($sub->combine_group, $processedGroups)) continue;
                    $processedGroups[] = $sub->combine_group;

                    $groupSubjects = $examSubjects->where('combine_group', $sub->combine_group);
                    
                    // Add individual components FIRST (Display Only)
                    foreach($groupSubjects as $gSub) {
                        $finalSubjects->push([
                            'id' => $gSub->id, 
                            'name' => $gSub->subject->name . ' (' . ($gSub->subject->code ?? '') . ')',
                            'creative_full_mark' => $gSub->creative_full_mark,
                            'mcq_full_mark' => $gSub->mcq_full_mark,
                            'practical_full_mark' => $gSub->practical_full_mark,
                            'total_full_mark' => $gSub->total_full_mark,
                            'is_combined' => false,
                            'display_only' => true, 
                            'component_ids' => [$gSub->id] 
                        ]);
                    }

                    // Add Combined Header
                    $finalSubjects->push([
                        'id' => 'group_' . $sub->combine_group,
                        'name' => $sub->combine_group,
                        'creative_full_mark' => $groupSubjects->sum('creative_full_mark'),
                        'mcq_full_mark' => $groupSubjects->sum('mcq_full_mark'),
                        'practical_full_mark' => $groupSubjects->sum('practical_full_mark'),
                        'total_full_mark' => $groupSubjects->sum('total_full_mark'),
                        'is_combined' => true,
                        'component_ids' => $groupSubjects->pluck('id')->toArray()
                    ]);

                } else {
                    $finalSubjects->push([
                        'id' => $sub->id,
                        'name' => $sub->subject->name,
                        'creative_full_mark' => $sub->creative_full_mark,
                        'mcq_full_mark' => $sub->mcq_full_mark,
                        'practical_full_mark' => $sub->practical_full_mark,
                        'total_full_mark' => $sub->total_full_mark,
                        'is_combined' => false, 
                        'component_ids' => [$sub->id]
                    ]);
                }
            }


            // 3. Fetch Student Results
            // Fetch students who are enrolled in this class/section for THIS academic year
            $enrollmentQuery = StudentEnrollment::where('school_id', $school->id)
                ->where('class_id', $classId)
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('status','active');
                
            if ($request->section_id) {
                $enrollmentQuery->where('section_id', $request->section_id);
            }
            
            $enrolledStudentIds = $enrollmentQuery->pluck('student_id')->unique()->values()->all();

            // Union all student ids (Existing Results + Enrolled)
            $existingResults = Result::where('exam_id', $examId)->whereIn('student_id', $enrolledStudentIds)->get();
            
            $allStudentIds = collect($existingResults->pluck('student_id'))
                ->merge($enrolledStudentIds)
                ->unique()
                ->values()
                ->all();
            
            $students = Student::with(['currentEnrollment.section', 'currentEnrollment.group'])
                ->whereIn('id', $allStudentIds)
                ->get();

            // Pre-load assigned subjects for all these students
            $allEnrollmentIds = StudentEnrollment::whereIn('student_id', $allStudentIds)
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('class_id', $classId)
                ->pluck('id');
            
            $assignedSubjectsMap = StudentSubject::with('enrollment')->whereIn('student_enrollment_id', $allEnrollmentIds)
                ->get()
                ->groupBy('enrollment.student_id');
            
            $studentAssignedSubjectIds = $assignedSubjectsMap->map(function($items) {
                return $items->pluck('subject_id')->unique()->toArray();
            });

            // Requirement: If student has NO subjects assigned in this exam, do not show them.
            $examSubjectIds = $examSubjects->pluck('subject_id')->toArray();
            $students = $students->filter(function($st) use ($studentAssignedSubjectIds, $examSubjectIds) {
                $assigned = $studentAssignedSubjectIds[$st->id] ?? [];
                return !empty(array_intersect($assigned, $examSubjectIds));
            });

            // Sort: Section Name (numeric safe?) -> Roll
            $students = $students->sortBy(function($st) {
                 $sec = $st->currentEnrollment->section->numeric_value ?? 999;
                 $roll = $st->currentEnrollment->roll_no ?? 9999;
                 return sprintf('%04d-%04d', $sec, $roll);
            });


            // Pre-load all marks for this exam to minimize queries
            $studentIds = $students->pluck('id');
            $marks = Mark::where('exam_id', $examId)
                ->whereIn('student_id', $studentIds)
                ->get();

            $resultsCollection = Result::where('exam_id', $examId)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');

            // Fetch optional subject mapping for all students
            $studentOptionalSubjectMap = StudentSubject::whereIn('student_enrollment_id', $allEnrollmentIds)
                ->where('is_optional', 1)
                ->with('subject')
                ->get()
                ->groupBy(function($item) {
                    return $item->enrollment->student_id;
                });

            // 4. Process Each Student
            foreach ($students as $student) {
                // Get optional subject code for this student
                $optSubRecord = $studentOptionalSubjectMap->get($student->id)?->first();
                $optSubCode = $optSubRecord ? $optSubRecord->subject->code : null;

                $res = $resultsCollection->get($student->id);
                if (!$res) {
                    // Create dummy result object if not calculated yet? 
                    // Or just skip/show empty? Better show empty row with student info
                     $res = new Result();
                     $res->student_id = $student->id;
                     $res->fourth_subject_id = $optSubRecord ? $optSubRecord->subject_id : null;
                     $res->fourth_subject_code = $optSubCode;
                     // set relation
                     $res->setRelation('student', $student);
                } else {
                     $res->setRelation('student', $student);
                     $res->fourth_subject_id = $optSubRecord ? $optSubRecord->subject_id : null;
                     $res->fourth_subject_code = $optSubCode;
                }


                $sid = $student->id;
                $studentMarks = $marks->where('student_id', $sid);
                
                $grandTotal = 0;
                $totalGpa = 0;
                $subjectCount = 0;
                $failedSubjectCount = 0; // New Fail Count
                $res->subject_results = collect(); 

                $studentOptionalId = $res->fourth_subject_id; // Need to ensure fourth_subject_id is loaded or fetchable

                // Determine Student Group
                $studentGroupId = $student->currentEnrollment ? $student->currentEnrollment->group_id : null;
                $currentStudentOptionalId = $res->fourth_subject_id;
                $assignedSubIds = $studentAssignedSubjectIds[$student->id] ?? [];

                foreach ($finalSubjects as $key => $fSub) {
                    // --- APPLICABILITY CHECK START ---
                    $isApplicable = false;
                    $subjId = $fSub['subject_id'] ?? null;
                    
                    if ($subjId) {
                        // Check if this subject_id is assigned to the student
                        $isApplicable = in_array($subjId, $assignedSubIds);
                    } else if (!empty($fSub['component_ids'])) {
                        // Merged subject: check if ANY component subject is assigned
                        foreach ($fSub['component_ids'] as $cid) {
                            $comp = $examSubjects->firstWhere('id', $cid);
                            if ($comp && in_array($comp->subject_id, $assignedSubIds)) {
                                $isApplicable = true;
                                break;
                            }
                        }
                    }

                    // --- SECOND CHANCE: If it has marks, it is applicable! ---
                    if (!$isApplicable) {
                         $hasAnyMark = false;
                         foreach ($fSub['component_ids'] as $eid) {
                             if ($studentMarks->firstWhere('exam_subject_id', $eid)) { $hasAnyMark = true; break; }
                         }
                         if ($hasAnyMark) $isApplicable = true;
                    }
                    
                    if (!$isApplicable) {
                         $res->subject_results->put($key, [
                            'grade' => '', 
                            'gpa' => '', 
                            'total' => '',
                            'display_only' => true, // Treat as display only (empty)
                            'is_not_applicable' => true // Custom flag for view
                        ]);
                        continue; // Skip processing and fail counting
                    }
                    // --- APPLICABILITY CHECK END ---

                    // The original logic to identify optional subject for GPA calculation
                    $isOptional = false;
                    foreach ($fSub['component_ids'] as $cid) {
                        $comp = $examSubjects->firstWhere('id', $cid);
                        if ($comp && $comp->subject_id == $currentStudentOptionalId) {
                            $isOptional = true;
                            break;
                        }
                    }

                    if (!empty($fSub['display_only'])) {
                         // Display only logic
                        $subTotal = 0; $subCreative = 0; $subMcq = 0; $subPractical = 0;
                        $isAbsent = false; $hasRecord = false;
                        
                        foreach ($fSub['component_ids'] as $eid) {
                            $m = $studentMarks->firstWhere('exam_subject_id', $eid);
                            if ($m) {
                                $hasRecord = true;
                                if ($m->is_absent) $isAbsent = true; 
                                $subTotal += $m->total_marks;
                                $subCreative += $m->creative_marks;
                                $subMcq += $m->mcq_marks;
                                $subPractical += $m->practical_marks;
                            }
                        }
                        
                        if (!$hasRecord) $subGrade = 'N/R';
                        elseif ($isAbsent) $subGrade = 'F'; 
                        else $subGrade = ''; 

                        $res->subject_results->put($key, [
                            'grade' => $subGrade, 'gpa' => 0, 'total' => $subTotal,
                            'creative' => $subCreative, 'mcq' => $subMcq, 'practical' => $subPractical,
                            'is_optional' => false, 'is_absent' => $isAbsent, 'display_only' => true
                        ]);
                        continue; 
                    }

                    // Combined Logic
                    $subTotal = 0; $subCreative = 0; $subMcq = 0; $subPractical = 0;
                    $subGP = 0; $subGrade = '';
                    $isAbsent = true; $hasRecord = false; $isFailed = false;
                    $totalCreativePass = 0; $totalMcqPass = 0; $totalPracticalPass = 0;
                    $groupHasEachPassType = false;

                    foreach ($fSub['component_ids'] as $eid) {
                        $m = $studentMarks->firstWhere('exam_subject_id', $eid);
                        $subComp = $examSubjects->firstWhere('id', $eid);
                        if ($subComp) {
                            $totalCreativePass += $subComp->creative_pass_mark;
                            $totalMcqPass += $subComp->mcq_pass_mark;
                            $totalPracticalPass += $subComp->practical_pass_mark;
                            if ($subComp->pass_type === 'each') $groupHasEachPassType = true;
                        }
                        if ($m) {
                            $hasRecord = true;
                            if (!$m->is_absent) $isAbsent = false;
                            $subTotal += $m->total_marks;
                            $subCreative += $m->creative_marks;
                            $subMcq += $m->mcq_marks;
                            $subPractical += $m->practical_marks;
                        }
                    }

                    if ($groupHasEachPassType) {
                        if ($totalCreativePass > 0 && $subCreative < $totalCreativePass) $isFailed = true;
                        if ($totalMcqPass > 0 && $subMcq < $totalMcqPass) $isFailed = true;
                        if ($totalPracticalPass > 0 && $subPractical < $totalPracticalPass) $isFailed = true;
                    }

                    if (!$hasRecord) {
                        $subGrade = 'N/R'; $subGP = 0;
                    } elseif ($isAbsent) {
                        $subGrade = 'F'; $subGP = 0;
                    } else {
                        $combTotalPass = 0; $combTotalFull = 0;
                        foreach ($fSub['component_ids'] as $eid) {
                            $subComp = $examSubjects->firstWhere('id', $eid);
                            if($subComp) { $combTotalPass += $subComp->total_pass_mark; $combTotalFull += $subComp->total_full_mark; }
                        }
                        if ($isFailed || $subTotal < $combTotalPass) {
                            $subGrade = 'F'; $subGP = 0;
                        } else {
                            $percent = $combTotalFull > 0 ? ($subTotal / $combTotalFull) * 100 : 0;
                            if ($percent >= 80) { $subGrade = 'A+'; $subGP = 5.00; }
                            elseif ($percent >= 70) { $subGrade = 'A'; $subGP = 4.00; }
                            elseif ($percent >= 60) { $subGrade = 'A-'; $subGP = 3.50; }
                            elseif ($percent >= 50) { $subGrade = 'B'; $subGP = 3.00; }
                            elseif ($percent >= 40) { $subGrade = 'C'; $subGP = 2.00; }
                            elseif ($percent >= 33) { $subGrade = 'D'; $subGP = 1.00; }
                            else { $subGrade = 'F'; $subGP = 0.00; }
                        }
                    }
                    
                    $res->subject_results->put($key, [
                        'grade' => $subGrade, 'gpa' => $subGP, 'total' => $subTotal,
                        'creative' => $subCreative, 'mcq' => $subMcq, 'practical' => $subPractical,
                        'is_optional' => $isOptional, 'is_absent' => $isAbsent
                    ]);

                    if ($isOptional) {
                        if ($subGP > 2.00) $totalGpa += ($subGP - 2.00);
                    } else {
                        $grandTotal += $subTotal;
                        $totalGpa += $subGP;
                        $subjectCount++;
                        if ($subGrade == 'F' || $subGrade == 'N/R') $failedSubjectCount++;
                    }
                }

                // Final Calculation
                $divisor = $exam->total_subjects_without_fourth > 0 
                    ? min($exam->total_subjects_without_fourth, $subjectCount)
                    : $subjectCount;

                $finalGpa = ($divisor > 0) ? round($totalGpa / $divisor, 2) : 0.00;
                if ($finalGpa > 5.00) $finalGpa = 5.00;

                $finalLetter = '';
                if ($failedSubjectCount > 0) {
                    $finalLetter = 'F'; $finalGpa = 0.00; 
                } elseif ($finalGpa >= 5.00) $finalLetter = 'A+';
                elseif ($finalGpa >= 4.00) $finalLetter = 'A';
                elseif ($finalGpa >= 3.50) $finalLetter = 'A-';
                elseif ($finalGpa >= 3.00) $finalLetter = 'B';
                elseif ($finalGpa >= 2.00) $finalLetter = 'C';
                elseif ($finalGpa >= 1.00) $finalLetter = 'D';
                else $finalLetter = 'F';

                $res->computed_total_marks = $grandTotal;
                $res->computed_gpa = $finalGpa;
                $res->computed_letter = $finalLetter;
                $res->computed_status = ($finalLetter == 'F') ? 'অকৃতকার্য' : 'উত্তীর্ণ';
                $res->fail_count = $failedSubjectCount;

                $results->push($res);
            }
        }
        
        $printTitle = $lang === 'bn' ? 'টেবুলেশন শিট' : 'Tabulation Sheet';
        
        $eName = ($lang === 'bn' && $exam->name_bn) ? $exam->name_bn : $exam->name;
        $cName = ($lang === 'bn' && $class->name_bn) ? $class->name_bn : $class->name;
        
        $printSubtitle = ($lang === 'bn' ? 'পরীক্ষা: ' : 'Exam: ') . $eName . ' | ' . 
                        ($lang === 'bn' ? 'শ্রেণি: ' : 'Class: ') . $cName . ' | ' . 
                        ($lang === 'bn' ? 'বছর: ' : 'Year: ') . $exam->academicYear->name;
        
        if ($sectionId) {
            $sec = Section::find($sectionId);
            $printSubtitle .= ' | ' . ($lang === 'bn' ? 'শাখা: ' : 'Section: ') . ($sec->name ?? $sec->section_name);
        }

        return view('principal.results.print-tabulation', compact('school', 'academicYears', 'classes', 'sections', 'exams', 'results', 'finalSubjects', 'exam', 'class', 'printTitle', 'printSubtitle', 'lang'));
    }

    // AJAX helpers for tabulation cascading selects
    // Note: examsByYear and sectionsByClass are already defined earlier in the file.
    

    
    public function studentsByClass(Request $request, School $school)
    {
        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $yearId = $request->academic_year_id;
        
        if(!$classId || !$yearId) return response()->json([]);
        
        $query = StudentEnrollment::where('school_id', $school->id)
            ->where('class_id', $classId)
            ->where('academic_year_id', $yearId)
            ->where('status', 'active');
            
        if($sectionId) {
            $query->where('section_id', $sectionId);
        }
        
        // Sorting logic: 
        // 1. Sort by roll_no (numeric or string handling needs care)
        // 2. Fallback to name
        
        $enrollments = $query->with('student')->get();
        
        $students = $enrollments->sortBy(function($e){
            // Ensure roll is treated numerically if possible
            return (int) $e->roll_no; 
        })->map(function($enrollment){
             $roll = $enrollment->roll_no ?? '-';
             $name = $enrollment->student->student_name_en ?: $enrollment->student->student_name_bn;
             return [
                'id' => $enrollment->student->id,
                'text' => $roll . ' - ' . $name
            ];
        })->values();

        return response()->json($students);
    }

    /**
     * Helper to calculate results for marksheet/tabulation
     */
    private function getCalculatedResults(School $school, $examId, $classId, $sectionId = null, $studentId = null)
    {
        $exam = Exam::with(['examSubjects.subject', 'academicYear'])->find($examId);
        if(!$exam) return null;
        
        $class = SchoolClass::find($classId);
        
        $examSubjects = $exam->examSubjects()->orderBy('display_order')->get();
        $examSubjectIds = $examSubjects->pluck('subject_id')->toArray();
        
        $classSubjects = ClassSubject::where('class_id', $classId)
            ->whereIn('subject_id', $examSubjectIds)
            ->with('group')
            ->get()
            ->keyBy('subject_id');

        // Sort examSubjects
         $examSubjects = $examSubjects->sort(function($a, $b) use ($classSubjects) {
            $csA = $classSubjects[$a->subject_id] ?? null;
            $csB = $classSubjects[$b->subject_id] ?? null;
            $groupA = $csA ? strtolower($csA->group->name ?? '') : '';
            $groupB = $csB ? strtolower($csB->group->name ?? '') : '';
            $priority = function($gName) {
                if (empty($gName)) return 0;
                if (str_contains($gName, 'science') || str_contains($gName, 'বিজ্ঞান')) return 1;
                if (str_contains($gName, 'humanities') || str_contains($gName, 'মানবিক')) return 2;
                if (str_contains($gName, 'business') || str_contains($gName, 'ব্যবসায়')) return 3;
                return 4;
            };
            $pA = $priority($groupA);
            $pB = $priority($groupB);
            if ($pA != $pB) return $pA <=> $pB;
            $optA = $csA ? $csA->is_optional : 0;
            $optB = $csB ? $csB->is_optional : 0;
            if ($optA != $optB) return $optA <=> $optB;
            $orderA = $csA ? $csA->order_no : 999;
            $orderB = $csB ? $csB->order_no : 999;
            return $orderA <=> $orderB;
        });

        // Results query
        $resultQuery = Result::with(['student'])
            ->forExam($exam->id)
            ->forClass($classId);
            
        if ($studentId) {
            $resultQuery->where('student_id', $studentId);
        } elseif ($sectionId) {
            $resultQuery->where('section_id', $sectionId);
        }
        
        $results = $resultQuery->get();
        
        // Fetch students enrolled
        $enrollmentQuery = StudentEnrollment::where('school_id', $school->id)
            ->where('class_id', $classId)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('status','active');
            
        if ($studentId) {
            $enrollmentQuery->where('student_id', $studentId);
        } elseif ($sectionId) {
            $enrollmentQuery->where('section_id', $sectionId);
        }
        
        $enrolledStudentIds = $enrollmentQuery->pluck('student_id')->unique()->values()->all();

        $allStudentIds = collect($results->pluck('student_id'))
            ->merge($enrolledStudentIds)
            ->unique()
            ->values()
            ->all();

        $marks = !empty($allStudentIds) ? Mark::forExam($exam->id)->whereIn('student_id', $allStudentIds)->get() : collect();
        
        // Pre-load assigned subjects
        $activeStudentIds = Student::whereIn('id', $allStudentIds)->where('status','active')->pluck('id')->unique()->values()->all();
        
        $allEnrollmentIds = StudentEnrollment::whereIn('student_id', $activeStudentIds)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_id', $classId) // Ensure class matches
            ->pluck('id');
        
        $assignedSubjectsMap = StudentSubject::with('enrollment')->whereIn('student_enrollment_id', $allEnrollmentIds)
            ->get()
            ->groupBy(function($item) {
                return $item->enrollment->student_id;
            });
        
        $studentAssignedSubjectIds = $assignedSubjectsMap->map(function($items) {
            return $items->pluck('subject_id')->unique()->toArray();
        });

        // Optional Subject Map
        $studentOptionalSubjectMap = StudentSubject::whereIn('student_enrollment_id', $allEnrollmentIds)
            ->where('is_optional', true)
            ->with('subject')
            ->get()
            ->groupBy(function($item) {
                return $item->enrollment->student_id;
            });

        $resultsCollection = collect();
        
        $studentsToProcess = Student::with(['currentEnrollment.section','currentEnrollment.group'])->whereIn('id', $activeStudentIds)->get();

        foreach ($studentsToProcess as $stu) {
            $sid = $stu->id;
            // Filter inactive/irrelevant
             $assigned = $studentAssignedSubjectIds[$sid] ?? [];
             if(empty(array_intersect($assigned, $examSubjectIds))) continue;

            $existing = $results->where('student_id', $sid)->first();
            if ($existing) {
                $existing->student = $stu;
                $existing->group_name = optional($stu->currentEnrollment->group)->name ?? null;
            } else {
                $fake = new Result();
                $fake->student_id = $sid;
                $fake->student = $stu;
                $fake->total_marks = 0;
                $fake->gpa = 0;
                $fake->letter_grade = null;
                $fake->result_status = 'not_computed';
                $fake->group_name = optional($stu->currentEnrollment->group)->name ?? null;
                $existing = $fake;
            }

            // Optional Subject
            $optSubRecord = $studentOptionalSubjectMap->get($sid)?->first();
            $existing->fourth_subject_code = $optSubRecord ? $optSubRecord->subject->code : null;
            $existing->fourth_subject_id = $optSubRecord ? $optSubRecord->subject_id : null;
            $existing->assigned_subject_ids = $assigned;
            
            $resultsCollection->push($existing);
        }

        // Prepare Final Subjects List
        $finalSubjects = collect();
        $processedGroups = [];

        foreach ($examSubjects as $sub) {
            if ($sub->combine_group) {
                $groupName = trim($sub->combine_group);
                $keyInd = 'ind_'.$sub->id;
                $finalSubjects->put($keyInd, [
                    'type' => 'individual',
                    'subject_id' => $sub->subject_id,
                    'name' => $sub->subject->name,
                    'code' => $sub->subject->code,
                    'creative_full_mark' => $sub->creative_full_mark,
                    'mcq_full_mark' => $sub->mcq_full_mark,
                    'practical_full_mark' => $sub->practical_full_mark,
                    'total_full_mark' => $sub->total_full_mark,
                    'display_only' => true,
                    'component_ids' => [$sub->id]
                ]);

                 $remainingInGroup = $examSubjects->where('combine_group', $groupName)->where('id', '>', $sub->id)->count();
                
                if ($remainingInGroup == 0 && !in_array($groupName, $processedGroups)) {
                    $allInGroup = $examSubjects->where('combine_group', $groupName);
                    $groupTotalFull = $allInGroup->sum('total_full_mark');
                    $groupPassFull = $allInGroup->sum(function($s){ return $s->creative_pass_mark + $s->mcq_pass_mark + $s->practical_pass_mark; });
                    $compIds = $allInGroup->pluck('id')->toArray();
                    $keyComb = 'comb_'.md5($groupName);
                    $finalSubjects->put($keyComb, [
                        'type' => 'combined',
                        'subject_id' => null,
                        'name' => $groupName,
                        'code' => '',
                        'creative_full_mark' => $allInGroup->sum('creative_full_mark'),
                        'mcq_full_mark' => $allInGroup->sum('mcq_full_mark'),
                        'practical_full_mark' => $allInGroup->sum('practical_full_mark'),
                        'total_full_mark' => $groupTotalFull,
                        'total_pass_mark' => $groupPassFull,
                        'pass_type' => 'combined', 
                        'component_ids' => $compIds,
                        'is_combined_result' => true
                    ]);
                    $processedGroups[] = $groupName;
                }

            } else {
                $key = 's_'.$sub->id;
                $finalSubjects->put($key, [
                    'type' => 'single',
                    'subject_id' => $sub->subject_id,
                    'name' => $sub->subject->name,
                    'code' => $sub->subject->code ?? '',
                    'creative_full_mark' => $sub->creative_full_mark,
                    'mcq_full_mark' => $sub->mcq_full_mark,
                    'practical_full_mark' => $sub->practical_full_mark,
                    'total_full_mark' => $sub->total_full_mark,
                    'creative_pass_mark' => $sub->creative_pass_mark,
                    'mcq_pass_mark' => $sub->mcq_pass_mark,
                    'practical_pass_mark' => $sub->practical_pass_mark,
                    'total_pass_mark' => ($sub->creative_pass_mark + $sub->mcq_pass_mark + $sub->practical_pass_mark),
                    'pass_type' => $sub->pass_type,
                    'component_ids' => [$sub->id]
                ]);
            }
        }

        // Calculation Loop
        foreach ($resultsCollection as $res) {
            $sid = $res->student_id;
            $studentMarks = $marks->where('student_id', $sid);
            $grandTotal = 0; $totalGpa = 0; $subjectCount = 0; $failedSubjectCount = 0; $hasAbsent = false;
            $res->subject_results = collect();
            $assignedSubIds = $res->assigned_subject_ids ?? [];
            $currentStudentOptionalId = $res->fourth_subject_id;

            foreach ($finalSubjects as $key => $fSub) {
                $isApplicable = false;
                $subjId = $fSub['subject_id'] ?? null;
                if ($subjId) { $isApplicable = in_array($subjId, $assignedSubIds); } 
                else if (!empty($fSub['component_ids'])) {
                    foreach ($fSub['component_ids'] as $cid) {
                        $comp = $examSubjects->firstWhere('id', $cid);
                        if ($comp && in_array($comp->subject_id, $assignedSubIds)) { $isApplicable = true; break; }
                    }
                }
                if (!$isApplicable) {
                     $hasAnyMark = false;
                     foreach ($fSub['component_ids'] as $eid) {
                         if ($studentMarks->firstWhere('exam_subject_id', $eid)) { $hasAnyMark = true; break; }
                     }
                     if ($hasAnyMark) $isApplicable = true;
                }
                
                if (!$isApplicable) continue;

                $isOptional = false;
                 foreach ($fSub['component_ids'] as $cid) {
                     $comp = $examSubjects->firstWhere('id', $cid);
                     if ($comp && $comp->subject_id == $currentStudentOptionalId) { $isOptional = true; break; }
                 }

                if (!empty($fSub['display_only'])) {
                    // Display only logic need to populate result for view
                    $subTotal = 0; $subCreative = 0; $subMcq = 0; $subPractical = 0;
                    $isAbsent = false; $hasRecord = false;
                    foreach ($fSub['component_ids'] as $eid) {
                        $m = $studentMarks->firstWhere('exam_subject_id', $eid);
                        if ($m) {
                            $hasRecord = true; if ($m->is_absent) $isAbsent = true; 
                            $subTotal += $m->total_marks; $subCreative += $m->creative_marks;
                            $subMcq += $m->mcq_marks; $subPractical += $m->practical_marks;
                        }
                    }
                    $subGrade = $hasRecord ? ($isAbsent ? 'Abs' : '') : 'N/R';
                     $res->subject_results->put($key, [
                        'grade' => $subGrade, 'gpa' => 0, 'total' => $subTotal,
                        'creative' => $subCreative, 'mcq' => $subMcq, 'practical' => $subPractical,
                        'is_optional' => false, 'is_absent' => $isAbsent, 'display_only' => true,
                        'name' => $fSub['name']
                    ]);
                    continue; 
                }

                $subTotal = 0; $subCreative = 0; $subMcq = 0; $subPractical = 0;
                $subGP = 0; $subGrade = '';
                $isAbsent = true; $hasRecord = false; $isFailed = false;
                $totalCreativePass = 0; $totalMcqPass = 0; $totalPracticalPass = 0;
                $groupHasEachPassType = false;

                foreach ($fSub['component_ids'] as $eid) {
                    $m = $studentMarks->firstWhere('exam_subject_id', $eid);
                    $subComp = $examSubjects->firstWhere('id', $eid); 
                    if ($subComp) {
                        $totalCreativePass += $subComp->creative_pass_mark;
                        $totalMcqPass += $subComp->mcq_pass_mark;
                        $totalPracticalPass += $subComp->practical_pass_mark;
                        if ($subComp->pass_type === 'each') $groupHasEachPassType = true;
                    }
                    if ($m) {
                        $hasRecord = true;
                        if (!$m->is_absent) $isAbsent = false;
                        $subTotal += $m->total_marks;
                        $subCreative += $m->creative_marks;
                        $subMcq += $m->mcq_marks;
                        $subPractical += $m->practical_marks;
                    }
                }

                if ($groupHasEachPassType) {
                    if ($totalCreativePass > 0 && $subCreative < $totalCreativePass) $isFailed = true;
                    if ($totalMcqPass > 0 && $subMcq < $totalMcqPass) $isFailed = true;
                    if ($totalPracticalPass > 0 && $subPractical < $totalPracticalPass) $isFailed = true;
                }

                if (!$hasRecord) { $subGrade = 'N/R'; $subGP = 0.00; } 
                elseif ($isAbsent) { $subGrade = 'F'; $subGP = 0.00; $hasAbsent = true; $isFailed = true; } 
                else {
                    $percent = ($fSub['total_full_mark'] > 0) ? ($subTotal / $fSub['total_full_mark']) * 100 : 0;
                    if ($isFailed || $subTotal < $fSub['total_pass_mark']) { $subGrade = 'F'; $subGP = 0.00; } 
                    else {
                        if ($percent >= 80) { $subGrade = 'A+'; $subGP = 5.00; }
                        elseif ($percent >= 70) { $subGrade = 'A'; $subGP = 4.00; }
                        elseif ($percent >= 60) { $subGrade = 'A-'; $subGP = 3.50; }
                        elseif ($percent >= 50) { $subGrade = 'B'; $subGP = 3.00; }
                        elseif ($percent >= 40) { $subGrade = 'C'; $subGP = 2.00; }
                        elseif ($percent >= 33) { $subGrade = 'D'; $subGP = 1.00; }
                        else { $subGrade = 'F'; $subGP = 0.00; }
                    }
                }

                $res->subject_results->put($key, [
                    'grade' => $subGrade, 'gpa' => $subGP, 'total' => $subTotal,
                    'creative' => $subCreative, 'mcq' => $subMcq, 'practical' => $subPractical,
                    'is_optional' => $isOptional, 'is_absent' => $isAbsent,
                    'name' => $fSub['name']
                ]);

                if ($isOptional) {
                    if ($subGP > 2.00) { $totalGpa += ($subGP - 2.00); }
                } else {
                    $grandTotal += $subTotal; $totalGpa += $subGP; $subjectCount++;
                    if ($subGrade == 'F' || $subGrade == 'N/R') $failedSubjectCount++;
                }
            }

            $divisor = $exam->total_subjects_without_fourth > 0 ? min($exam->total_subjects_without_fourth, $subjectCount) : $subjectCount;
            $finalGpa = ($divisor > 0) ? round($totalGpa / $divisor, 2) : 0.00;
            if ($finalGpa > 5.00) $finalGpa = 5.00;
            $finalLetter = '';
            if ($failedSubjectCount > 0) { $finalLetter = 'F'; $finalGpa = 0.00; } 
            elseif ($finalGpa >= 5.00) $finalLetter = 'A+';
            elseif ($finalGpa >= 4.00) $finalLetter = 'A';
            elseif ($finalGpa >= 3.50) $finalLetter = 'A-';
            elseif ($finalGpa >= 3.00) $finalLetter = 'B';
            elseif ($finalGpa >= 2.00) $finalLetter = 'C';
            elseif ($finalGpa >= 1.00) $finalLetter = 'D';
            else $finalLetter = 'F';

            $res->computed_total_marks = $grandTotal;
            $res->computed_gpa = $finalGpa;
            $res->computed_letter = $finalLetter;
            $res->computed_status = ($finalLetter == 'F') ? 'অকৃতকার্য' : 'উত্তীর্ণ';
            $res->fail_count = $failedSubjectCount;
        }

        $results = $resultsCollection->sortBy(function($r){
            $section = optional(optional($r->student)->currentEnrollment)->section;
            $secOrder = $section ? ($section->numeric_value ?? $section->id) : 999999;
            $roll = optional(optional($r->student)->currentEnrollment)->roll_no ?? 999999;
            return sprintf('%09d-%09d', $secOrder, $roll);
        })->values();

        return compact('results', 'finalSubjects', 'exam', 'class', 'examSubjects');
    }
}


