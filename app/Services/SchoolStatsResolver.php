<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolStatsSetting;
use App\Models\Student;

class SchoolStatsResolver
{
    /**
     * @return array{mode: string, students: ?int, teachers: ?int, staff: ?int, classes: ?int, founding_year: ?int, experience_years: ?int}
     */
    public function resolve(School $school, ?SchoolStatsSetting $settings): array
    {
        if ($settings?->isStatic()) {
            $foundingYear = $settings->static_founding_year;

            return [
                'mode' => SchoolStatsSetting::MODE_STATIC,
                'students' => $settings->static_students_count,
                'teachers' => $settings->static_teachers_count,
                'staff' => $settings->static_staff_count,
                'classes' => $settings->static_classes_count,
                'founding_year' => $foundingYear,
                'experience_years' => $foundingYear ? max(0, now()->year - $foundingYear) : null,
            ];
        }

        $foundingYear = $school->founding_year ? (int) $school->founding_year : null;

        return [
            'mode' => SchoolStatsSetting::MODE_DYNAMIC,
            'students' => Student::where('school_id', $school->id)->where('status', 'active')->count(),
            'teachers' => \App\Models\Teacher::where('school_id', $school->id)->where('status', 'active')->count(),
            'staff' => null,
            'classes' => $school->classes()->count(),
            'founding_year' => $foundingYear,
            'experience_years' => $foundingYear ? max(0, now()->year - $foundingYear) : null,
        ];
    }

    /**
     * The live/dynamic values, used as read-only preview on the settings screen
     * regardless of which mode is currently active.
     *
     * @return array{students: int, teachers: int, classes: int, founding_year: ?int}
     */
    public function dynamicPreview(School $school): array
    {
        $foundingYear = $school->founding_year ? (int) $school->founding_year : null;

        return [
            'students' => Student::where('school_id', $school->id)->where('status', 'active')->count(),
            'teachers' => \App\Models\Teacher::where('school_id', $school->id)->where('status', 'active')->count(),
            'classes' => $school->classes()->count(),
            'founding_year' => $foundingYear,
        ];
    }
}
