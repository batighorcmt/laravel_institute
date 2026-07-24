<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentProfileResource extends JsonResource
{
    // Resource receives a Student model
    public function toArray($request): array
    {
        $st = $this->resource;
        $en = $st?->currentEnrollment;

        $photoPath = $st?->photo_url;
        $photoUrl = null;
        if ($photoPath) {
            if (! str_starts_with($photoPath, 'http')) {
                $storageUrl = Storage::url($photoPath);
                $photoUrl = rtrim(config('app.url'), '/') . '/' . ltrim($storageUrl, '/');
            } else {
                $photoUrl = $photoPath;
            }
        }

        // Compose present address from parts if available

        $presentParts = array_filter([
            $st?->present_village,
            $st?->present_post_office,
            $st?->present_upazilla,
            $st?->present_district,
        ]);
        $presentAddress = $presentParts ? implode(', ', $presentParts) : ($st?->present_address ?? null);

        // Compose permanent address from parts if available
        $permanentParts = array_filter([
            $st?->permanent_village,
            $st?->permanent_post_office,
            $st?->permanent_upazilla,
            $st?->permanent_district,
        ]);
        $permanentAddress = $permanentParts ? implode(', ', $permanentParts) : ($st?->permanent_address ?? null);


        // Try to include BN variants when fields exist on the model
        $presentAddressBn = $st?->present_address_bn ?? null;
        $permanentAddressBn = $st?->permanent_address_bn ?? null;

        $base = $st?->attributesToArray() ?? [];
        // Map common attribute names to what mobile app expects if they differ
        $base['dob'] = $base['date_of_birth'] ?? null;
        $base['student_code'] = $base['student_id'] ?? null;

        // Attendance summary, scoped to the current academic year (when
        // known) so it reflects "this session" rather than all-time history.
        $attendanceCounts = \App\Models\Attendance::where('student_id', $st?->id)
            ->when($en?->academicYear, fn ($q) => $q->whereBetween('date', [
                $en->academicYear->start_date->toDateString(),
                $en->academicYear->end_date->toDateString(),
            ]))
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');
        $leaveDays = \App\Models\StudentLeave::where('student_id', $st?->id)
            ->where('status', 'approved')
            ->when($en?->academicYear, fn ($q) => $q->where(function ($qq) use ($en) {
                $qq->whereBetween('start_date', [$en->academicYear->start_date, $en->academicYear->end_date])
                    ->orWhereBetween('end_date', [$en->academicYear->start_date, $en->academicYear->end_date]);
            }))
            ->get()
            ->sum(fn ($l) => $l->start_date->diffInDays($l->end_date) + 1);
        $attendanceStats = [
            'present' => (int) ($attendanceCounts['present'] ?? 0),
            'absent' => (int) ($attendanceCounts['absent'] ?? 0),
            'late' => (int) ($attendanceCounts['late'] ?? 0),
            'leave' => (int) $leaveDays,
        ];

        return array_merge($base, [
            'photo_url' => $photoUrl,
            'present_address' => $presentAddress,
            'present_address_bn' => $presentAddressBn,
            'permanent_address' => $permanentAddress,
            'permanent_address_bn' => $permanentAddressBn,
            'id' => $st?->id,
            'student_id' => $st?->student_id,
            'student_code' => $st?->student_id, 
            'student_name_en' => $st?->student_name_en,
            'student_name_bn' => $st?->student_name_bn,
            'name' => $st?->full_name,
            'name_en' => $st?->student_name_en,
            'name_bn' => $st?->student_name_bn,
            'gender' => $st?->gender,
            'date_of_birth' => optional($st?->date_of_birth)->toDateString(),
            'dob' => optional($st?->date_of_birth)->toDateString(), 
            'phone' => $st?->guardian_phone,
            'email' => $st?->email ?? null,
            'blood_group' => $st?->blood_group ?? null,
            'religion' => $st?->religion ?? null,
            'photo_url' => $photoUrl,
            'class' => $en?->class?->name,
            'section' => $en?->section?->name,
            'group' => $en?->group?->name,
            'roll' => $en?->roll_no,
            'academic_year' => $en?->academicYear?->name,
            'year' => $en?->academicYear?->name,
            'session' => $en?->academicYear?->name, 
            'shift' => $en?->class?->shift?->name,
            'medium' => $en?->class?->medium,
            'class_teacher' => $en?->section?->class_teacher_name ?? $en?->section?->classTeacher?->full_name,
            'class_teacher_phone' => $en?->section?->classTeacher?->phone,
            'optional_subject' => $st?->optionalSubject?->name,
            'school_name' => $st?->school?->name,
            'school_name_bn' => $st?->school?->name_bn,

            // Guardian fields (top-level) so mobile clients can easily read them
            'guardian_name' => $st?->guardian_name_en ?? $st?->guardian_name_bn ?? $st?->father_name ?? $st?->mother_name,
            'guardian_name_en' => $st?->guardian_name_en,
            'guardian_name_bn' => $st?->guardian_name_bn,
            'guardian_phone' => $st?->guardian_phone,
            'guardian_relation' => $st?->guardian_relation,

            // Specific parent fields at top level for picking robustness
            'father_name' => $st?->father_name,
            'father' => $st?->father_name,
            'father_name_bn' => $st?->father_name_bn,
            'mother_name' => $st?->mother_name,
            'mother' => $st?->mother_name,
            'mother_name_bn' => $st?->mother_name_bn,
            'father_phone' => null, 
            'mother_phone' => null, 

            'guardians' => [
                'father_name' => $st?->father_name,
                'father_name_bn' => $st?->father_name_bn,
                'father_phone' => null,
                'mother_name' => $st?->mother_name,
                'mother_name_bn' => $st?->mother_name_bn,
                'mother_phone' => null,
            ],
            // Raw DB columns for addressing
            'village' => $st?->present_village,
            'gram' => $st?->present_village,
            'present_village' => $st?->present_village,
            'present_para_moholla' => $st?->present_para_moholla,
            'present_post_office' => $st?->present_post_office,
            'present_upazilla' => $st?->present_upazilla,
            'present_district' => $st?->present_district,
            'thana' => $st?->present_upazilla,
            'zilla' => $st?->present_district,
            'permanent_village' => $st?->permanent_village,
            'permanent_para_moholla' => $st?->permanent_para_moholla,
            'permanent_post_office' => $st?->permanent_post_office,
            'permanent_upazilla' => $st?->permanent_upazilla,
            'permanent_district' => $st?->permanent_district,


            // Admission & previous fields from students table
            'admission_date' => optional($st?->admission_date)->toDateString(),
            'previous_school' => $st?->previous_school ?? null,
            'pass_year' => $st?->pass_year ?? null,
            'previous_result' => $st?->previous_result ?? null,
            'previous_remarks' => $st?->previous_remarks ?? null,
            'status' => $st?->status ?? null,
            'student_status' => $st?->status ?? null,

            // New fields for comprehensive profile
            'attendance_stats' => $attendanceStats,
            'working_days' => (int) $attendanceCounts->sum() + (int) $leaveDays,
            'enrollment_history' => $st?->relationLoaded('enrollments') ? $st?->enrollments->map(fn($en) => [
                'id' => $en->id,
                'academic_year' => $en->academicYear?->name,
                'class' => $en->class?->name,
                'section' => $en->section?->name,
                'group' => $en->group?->name,
                'roll' => $en->roll_no,
                'status' => $en->status,
            ]) : [],
            'memberships' => $st?->relationLoaded('teams') ? $st?->teams->map(fn($tm) => [
                'id' => $tm->id,
                'name' => $tm->name,
                'status' => $tm->pivot?->status,
                'joined_at' => $tm->pivot?->joined_at,
            ]) : [],

            // Subjects mapped to the student's current class, for the current
            // academic year — used by the ইতিহাস tab's "current session
            // subjects" block.
            'current_subjects' => ($en && $en->class_id)
                ? \App\Models\Subject::where('school_id', $st?->school_id)
                    ->whereHas('classMappings', function ($q) use ($en) {
                        $q->where('class_id', $en->class_id)->where('status', 'active');
                    })
                    ->orderBy('name')
                    ->pluck('name')
                    ->values()
                : [],

            // Per-subject lesson-evaluation breakdown for the current
            // academic year (how many days per status, per subject) — used
            // by the ইতিহাস tab's "lesson evaluation record" block.
            'lesson_evaluation_summary' => $st
                ? \App\Models\LessonEvaluationRecord::where('student_id', $st->id)
                    ->whereHas('lessonEvaluation', function ($q) use ($st, $en) {
                        $q->where('school_id', $st->school_id);
                        if ($en?->academicYear) {
                            $q->whereBetween('evaluation_date', [
                                $en->academicYear->start_date->toDateString(),
                                $en->academicYear->end_date->toDateString(),
                            ]);
                        }
                    })
                    ->join('lesson_evaluations', 'lesson_evaluations.id', '=', 'lesson_evaluation_records.lesson_evaluation_id')
                    ->join('subjects', 'subjects.id', '=', 'lesson_evaluations.subject_id')
                    ->select('subjects.name as subject_name', 'lesson_evaluation_records.status', DB::raw('count(*) as cnt'))
                    ->groupBy('subjects.name', 'lesson_evaluation_records.status')
                    ->get()
                    ->groupBy('subject_name')
                    ->map(function ($rows, $subjectName) {
                        $counts = $rows->pluck('cnt', 'status');
                        return [
                            'subject_name' => $subjectName,
                            'total' => (int) $counts->sum(),
                            'completed' => (int) ($counts['completed'] ?? 0),
                            'partial' => (int) ($counts['partial'] ?? 0),
                            'not_done' => (int) ($counts['not_done'] ?? 0),
                            'absent' => (int) ($counts['absent'] ?? 0),
                        ];
                    })
                    ->values()
                : [],
            
            // Approved leave calendar — every date across all approved leave
            // applications, so the app can render a leave calendar and treat
            // these dates as leave (not absent) in attendance stats.
            'leave_calendar' => ($leaves = \App\Models\StudentLeave::where('student_id', $st?->id)
                ->where('status', 'approved')
                ->orderByDesc('start_date')
                ->get())->flatMap(fn ($l) => $l->dateRange())->values(),
            'leave_summary' => [
                'total_days' => $leaves->sum(fn ($l) => $l->start_date->diffInDays($l->end_date) + 1),
                'applications' => $leaves->map(fn ($l) => [
                    'id' => $l->id,
                    'title' => $l->title,
                    'start_date' => $l->start_date->toDateString(),
                    'end_date' => $l->end_date->toDateString(),
                ])->values(),
            ],

            'today_attendance' => [
                'class' => (\App\Models\StudentLeave::where('student_id', $st?->id)
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->exists()) ? [
                        'status' => 'leave',
                        'updated_at' => null,
                    ] : (($ca = \App\Models\Attendance::where('student_id', $st?->id)
                    ->whereDate('date', now())
                    ->select('status', 'updated_at')
                    ->first()) ? [
                        'status' => $ca->status,
                        'updated_at' => optional($ca->updated_at)->toIso8601String()
                    ] : null),
                'extra_classes' => \App\Models\ExtraClassEnrollment::where('student_id', $st?->id)
                    ->where('status', 'active')
                    ->with(['extraClass'])
                    ->get()
                    ->map(function($en) use ($st) {
                        $att = \App\Models\ExtraClassAttendance::where('student_id', $st?->id)
                            ->where('extra_class_id', $en->extra_class_id)
                            ->whereDate('date', now())
                            ->first();
                        return [
                            'name' => $en->extraClass?->name,
                            'status' => $att?->status,
                            'time' => optional($att?->updated_at)->toIso8601String(),
                        ];
                    }),
                'teams' => $st?->teams()->wherePivot('status', 'active')->get()->map(function($tm) use ($st) {
                    $att = \App\Models\TeamAttendance::where('student_id', $st?->id)
                        ->where('team_id', $tm->id)
                        ->whereDate('date', now())
                        ->first();
                    return [
                        'name' => $tm->name,
                        'status' => $att?->status,
                        'time' => optional($att?->updated_at)->toIso8601String(),
                    ];
                }),
            ],

            'today_evaluations' => ($en && $en->class_id && $en->section_id) ? 
                \App\Models\RoutineEntry::where('school_id', $st->school_id)
                    ->where('class_id', $en->class_id)
                    ->where('section_id', $en->section_id)
                    ->where('day_of_week', strtolower(now()->format('l')))
                    ->with(['subject'])
                    ->orderBy('period_number')
                    ->get()
                    ->filter(function($routine) use ($en) {
                        // Filter by assigned subjects if assignments exist
                        $assignedIds = \App\Models\StudentSubject::where('student_enrollment_id', $en->id)
                                        ->where('status', 'active')
                                        ->pluck('subject_id')
                                        ->toArray();
                        
                        if (!empty($assignedIds)) {
                            return in_array($routine->subject_id, $assignedIds);
                        }
                        
                        // Otherwise, show all (might need further filtering by group if routine has group_id)
                        return true;
                    })
                    ->map(function($routine) use ($st) {
                        $eval = \App\Models\LessonEvaluation::where('routine_entry_id', $routine->id)
                            ->whereDate('evaluation_date', now())
                            ->with(['records' => fn($q) => $q->where('student_id', $st->id)])
                            ->first();
                        
                        return [
                            'period' => $routine->period_number,
                            'subject' => $routine->subject?->name,
                            'status' => $eval?->records->first()?->status,
                            'notes' => $eval?->notes,
                            'time' => optional($eval?->evaluation_time)->toIso8601String(),
                        ];
                    })->values() : [],

        ]);
    }
}
