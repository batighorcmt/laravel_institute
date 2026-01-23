<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\DocumentRecord;
use App\Models\School;
use Carbon\Carbon;

class DocumentMemoService
{
    /**
     * Generate memo no based on configured format or default format: <school_code>/<type>/<academic_year_name>/<serial_in_year>
     * Type will be one of: prottayon|certificate|testimonial
     */
    public static function generate(School $school, string $type, ?Carbon $issuedAt = null, ?string $yearName = null, ?array $format = null, ?\App\Models\Student $student = null): string
    {
        $issuedAt = $issuedAt ?: Carbon::now();
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearName = $yearName ?: ($currentYear ? ($currentYear->name ?: (string)$issuedAt->year) : (string)$issuedAt->year);

        // Determine academic year date window
        if ($yearName) {
            $start = Carbon::createFromDate((int)$yearName, 1, 1)->startOfDay();
            $end = Carbon::createFromDate((int)$yearName, 12, 31)->endOfDay();
        } else {
            $start = $currentYear && $currentYear->start_date ? Carbon::parse($currentYear->start_date)->startOfDay() : Carbon::createFromDate((int)$yearName, 1, 1)->startOfDay();
            $end = $currentYear && $currentYear->end_date ? Carbon::parse($currentYear->end_date)->endOfDay() : Carbon::createFromDate((int)$yearName, 12, 31)->endOfDay();
        }

        // Count documents in this academic year for this school and type
        $count = DocumentRecord::where('school_id', $school->id)
            ->where('type', $type)
            ->whereBetween('issued_at', [$start, $end])
            ->count();
        $serial = $count + 1;

        $schoolCode = $school->code ?? 'SCH';

        // Get configured format and setting
        $setting = null;
        if (!$format) {
            $setting = \App\Models\DocumentSetting::where('school_id', $school->id)->where('page', $type)->first();
            $format = $setting && $setting->memo_format ? $setting->memo_format : ['institution_code', 'type', 'academic_year', 'serial_no'];
        } else {
            $setting = \App\Models\DocumentSetting::where('school_id', $school->id)->where('page', $type)->first();
        }

        $parts = [];
        foreach ($format as $keyword) {
            switch ($keyword) {
                case 'institution_code':
                    $parts[] = $schoolCode;
                    break;
                case 'type':
                    $parts[] = $type;
                    break;
                case 'academic_year':
                    $parts[] = $yearName;
                    break;
                case 'serial_no':
                    $parts[] = str_pad((string)$serial, 4, '0', STR_PAD_LEFT);
                    break;
                case 'custom_text':
                    $parts[] = $setting && $setting->custom_text ? implode('-', $setting->custom_text) : 'CUSTOM';
                    break;
                case 'class':
                    if ($student && $student->class) {
                        $parts[] = $student->class->numeric ?? 'CLASS';
                    } else {
                        $parts[] = 'CLASS';
                    }
                    break;
                default:
                    $parts[] = $keyword;
            }
        }

        return implode('/', $parts);
    }
}
