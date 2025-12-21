<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\DocumentRecord;
use App\Models\School;
use Carbon\Carbon;

class DocumentMemoService
{
    /**
     * Generate memo no in format: <school_code><> <type> <> <academic_year_name> <> <serial_in_year>
     * Type will be one of: prottayon|certificate|testimonial
     */
    public static function generate(School $school, string $type, ?Carbon $issuedAt = null): string
    {
        $issuedAt = $issuedAt ?: Carbon::now();
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearName = $currentYear ? ($currentYear->name ?: (string)$issuedAt->year) : (string)$issuedAt->year;

        // Determine academic year date window
        $start = $currentYear && $currentYear->start_date ? Carbon::parse($currentYear->start_date)->startOfDay() : Carbon::createFromDate((int)$yearName, 1, 1)->startOfDay();
        $end   = $currentYear && $currentYear->end_date ? Carbon::parse($currentYear->end_date)->endOfDay() : Carbon::createFromDate((int)$yearName, 12, 31)->endOfDay();

        // Count documents in this academic year for this school and type
        $count = DocumentRecord::where('school_id', $school->id)
            ->where('type', $type)
            ->whereBetween('issued_at', [$start, $end])
            ->count();
        $serial = $count + 1;

        $schoolCode = $school->code ?? 'SCH';
        // Bengali/English mixed is acceptable; using ASCII delimiters
        return $schoolCode . '<>' . $type . '<>' . $yearName . '<>' . str_pad((string)$serial, 4, '0', STR_PAD_LEFT);
    }
}
