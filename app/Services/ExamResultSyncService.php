<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Result;
use App\Models\School;
use App\Traits\ResultCalculationTrait;

class ExamResultSyncService
{
    use ResultCalculationTrait;

    /**
     * Recalculate and persist all student results for an exam class (same logic as tabulation/marksheet).
     */
    public function syncExamClass(School $school, Exam $exam): void
    {
        $classId = $exam->class_id;
        if (! $classId) {
            return;
        }

        $calcData = $this->getCalculatedResults($school, $exam->id, $classId);
        if (! $calcData || $calcData['results']->isEmpty()) {
            return;
        }

        $ranked = $calcData['results']->sort(function ($a, $b) {
            if (($a->fail_count ?? 0) !== ($b->fail_count ?? 0)) {
                return ($a->fail_count ?? 0) <=> ($b->fail_count ?? 0);
            }

            return ($b->computed_total_marks ?? 0) <=> ($a->computed_total_marks ?? 0);
        })->values();

        $meritPosition = 0;

        foreach ($ranked as $res) {
            $student = $res->student;
            if (! $student) {
                continue;
            }

            $letter = (string) ($res->computed_letter ?? 'F');
            $failCount = (int) ($res->fail_count ?? 0);
            $gpa = (float) ($res->computed_gpa ?? 0);
            $isPass = $letter !== 'F' && $failCount === 0 && $gpa > 0;

            $meritPos = null;
            if ($isPass) {
                $meritPosition++;
                $meritPos = $meritPosition;
            }

            $resultStatus = 'incomplete';
            if ($failCount > 0 || $letter === 'F') {
                $resultStatus = 'fail';
            } elseif ($gpa > 0) {
                $resultStatus = 'pass';
            }

            $existing = Result::query()
                ->where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->first();

            Result::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                ],
                [
                    'class_id' => $classId,
                    'section_id' => $res->section_id ?? optional($student->currentEnrollment)->section_id,
                    'total_marks' => $res->computed_total_marks ?? 0,
                    'total_possible_marks' => 0,
                    'percentage' => 0,
                    'gpa' => $gpa,
                    'letter_grade' => $letter,
                    'result_status' => $resultStatus,
                    'failed_subjects_count' => $failCount,
                    'absent_subjects_count' => 0,
                    'class_position' => $res->class_position ?? null,
                    'section_position' => $res->section_position ?? null,
                    'merit_position' => $meritPos,
                    'is_published' => $existing?->is_published ?? false,
                    'published_at' => $existing?->published_at,
                ]
            );
        }
    }

    public function syncAfterMarkSaved(School $school, Exam $exam): void
    {
        $this->syncExamClass($school, $exam);
    }
}
