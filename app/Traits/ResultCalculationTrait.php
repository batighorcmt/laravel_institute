<?php

namespace App\Traits;

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
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\WeeklyHoliday;
use Carbon\CarbonPeriod;

trait ResultCalculationTrait
{
    protected function getCalculatedResults(School $school, $examId, $classId, $sectionId = null, $studentId = null, $academicYearId = null)
    {
        $exam = Exam::with(['academicYear', 'class'])->findOrFail($examId);
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

        $results = $resultQuery->get();

        // Fetch students enrolled in THIS academic year only
        $enrollmentQuery = StudentEnrollment::where('school_id', $school->id)
            ->where('class_id', $classId)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('status','active')
            ->when(!empty($exam->section_ids), function ($query) use ($exam) {
                $query->whereIn('section_id', $exam->section_ids);
            })
            ->when(!empty($exam->group_ids), function ($query) use ($exam) {
                $query->whereIn('group_id', $exam->group_ids);
            });

        $enrolledStudentIds = $enrollmentQuery->pluck('student_id')->unique()->values()->all();

        // Combine results students with enrolled students (both filtered to correct AY)
        $allStudentIds = collect($results->pluck('student_id'))
            ->merge($enrolledStudentIds)
            ->unique()
            ->values()
            ->all();

        // --- ATTENDANCE CALCULATION LOGIC START ---
        $academicYear = $exam->academicYear;
        $startDate = $academicYear ? \Carbon\Carbon::parse($academicYear->start_date) : null;
        $endDate = $exam->start_date ? \Carbon\Carbon::parse($exam->start_date)->subDay() : null;

        $totalSchoolDays = 0;
        $allAttendances = collect();
        if ($startDate && $endDate && $startDate->lte($endDate)) {
            $weeklyHolidays = WeeklyHoliday::where('school_id', $school->id)->active()->pluck('day_number')->toArray();
            $holidays = Holiday::where('school_id', $school->id)->active()
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->pluck('date')
                ->map(fn($d) => $d->format('Y-m-d'))
                ->toArray();

            $period = CarbonPeriod::create($startDate, $endDate);
            foreach ($period as $date) {
                $dayNum = ($date->dayOfWeek == 0) ? 7 : $date->dayOfWeek;
                if (in_array($dayNum, $weeklyHolidays)) continue;
                if (in_array($date->format('Y-m-d'), $holidays)) continue;
                $totalSchoolDays++;
            }

            $allAttendances = Attendance::whereIn('student_id', $allStudentIds)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get()
                ->groupBy('student_id');
        }
        // --- ATTENDANCE CALCULATION LOGIC END ---

        $marks = !empty($allStudentIds) ? Mark::forExam($exam->id)->whereIn('student_id', $allStudentIds)->get() : collect();

        // Pre-load assigned subjects
        $activeStudentIds = Student::whereIn('id', $allStudentIds)->where('status','active')->pluck('id')->unique()->values()->all();

        $allEnrollmentIds = StudentEnrollment::whereIn('student_id', $activeStudentIds)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_id', $classId)
            ->when(!empty($exam->section_ids), function ($query) use ($exam) {
                $query->whereIn('section_id', $exam->section_ids);
            })
            ->when(!empty($exam->group_ids), function ($query) use ($exam) {
                $query->whereIn('group_id', $exam->group_ids);
            })
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
            $grandTotal = 0; $totalGpa = 0; $subjectCount = 0; $failedSubjectCount = 0; $hasAbsent = false; $ungradedSubjectCount = 0;
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
                    // Display only logic
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

                $grandTotal += $subTotal;
                if ($subGrade === 'N/R') {
                    // No mark record yet for this subject: exclude it entirely from the
                    // GPA/pass-fail average instead of counting it as a failure. Otherwise
                    // a partially-graded result (e.g. 1 of 5 subjects entered) computes as
                    // GPA≈0 / letter 'F' ("failing") when it is really just incomplete.
                    $ungradedSubjectCount++;
                } elseif ($isOptional) {
                    if ($subGP > 2.00) { $totalGpa += ($subGP - 2.00); }
                } else {
                    $totalGpa += $subGP; $subjectCount++;
                    if ($subGrade == 'F') $failedSubjectCount++;
                }
            }

            $divisor = ($exam->total_subjects_without_fourth ?? 0) > 0 ? min($exam->total_subjects_without_fourth, $subjectCount) : $subjectCount;
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
            $res->is_complete = ($ungradedSubjectCount === 0);
            $res->section_id = optional(optional($stu->currentEnrollment)->section)->id;

            // Attendance Summary
            $stuAttendance = $allAttendances->get($sid) ?: collect();
            $presentArray = ['present', 'late', 'P', 'L'];
            $presentDays = $stuAttendance->whereIn('status', $presentArray)->count();

            $res->attendance_days = $totalSchoolDays;
            $res->attendance_present = $presentDays;
            $res->attendance_absent = max(0, $totalSchoolDays - $presentDays);
            $res->attendance_rate = ($totalSchoolDays > 0) ? round(($presentDays / $totalSchoolDays) * 100, 2) : 0;
        }

        // Ranking
        $classRanked = $resultsCollection->sort(function($a, $b) {
            if ($a->fail_count != $b->fail_count) return $a->fail_count <=> $b->fail_count;
            return $b->computed_total_marks <=> $a->computed_total_marks;
        })->values();

        foreach($classRanked as $index => $r) {
            $r->class_position = $index + 1;
        }

        $sectionsGroup = $resultsCollection->groupBy('section_id');
        foreach($sectionsGroup as $secId => $secResults) {
            $secRanked = $secResults->sort(function($a, $b) {
                if ($a->fail_count != $b->fail_count) return $a->fail_count <=> $b->fail_count;
                return $b->computed_total_marks <=> $a->computed_total_marks;
            })->values();
            foreach($secRanked as $index => $r) {
                $r->section_position = $index + 1;
            }
        }

        // Highest Marks
        $highestMarksMap = [];
        foreach ($resultsCollection as $res) {
            foreach ($res->subject_results as $key => $sData) {
                if (isset($sData['total']) && is_numeric($sData['total'])) {
                    $score = (float) $sData['total'];
                    if (!isset($highestMarksMap[$key]) || $score > $highestMarksMap[$key]) {
                        $highestMarksMap[$key] = $score;
                    }
                }
            }
        }

        foreach ($resultsCollection as $res) {
            $updatedResults = collect();
            foreach ($res->subject_results as $key => $sData) {
                $fSub = $finalSubjects->get($key);
                $sData['full_mark'] = $fSub['total_full_mark'] ?? 0;
                $sData['highest_mark'] = $highestMarksMap[$key] ?? 0;
                $updatedResults->put($key, $sData);
            }
            $res->subject_results = $updatedResults;
        }

        $results = $resultsCollection->values();

        if ($studentId) {
            $results = $results->where('student_id', $studentId)->values();
        } elseif ($sectionId) {
            $results = $results->where('section_id', $sectionId)->values();
        }

        return [
            'results' => $results,
            'finalSubjects' => $finalSubjects,
            'exam' => $exam,
            'class' => $class,
            'examSubjects' => $examSubjects
        ];
    }
}
