@section('title', 'Academic Transcript')

@push('print_head')
<style>
    @page { size: A4 portrait; margin: 10mm; }
    .print-content { font-family: 'Times New Roman', serif; color: #000; position: relative; }
    
    /* Watermark / Background Pattern */
    .bg-pattern {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background-image: url('{{ asset("images/transcript-bg.png") }}'); /* Placeholder if any */
        opacity: 0.1; z-index: -1; pointer-events: none;
    }

    /* Header */
    .header-section { text-align: center; margin-bottom: 20px; position: relative; }
    .header-section h1 { font-size: 22pt; font-weight: bold; margin: 0; text-transform: uppercase; color: #1a4d2e; } /* Greenish specific color if needed, using dark green */
    .header-section h2 { font-size: 14pt; margin: 5px 0; font-weight: normal; }
    .header-section .serial-no { position: absolute; top: 0; left: 0; font-size: 10pt; font-weight: bold; }
    .header-logo { width: 80px; height: 80px; margin: 5px auto; display: block; }
    
    .transcript-title { 
        text-align: center; 
        font-size: 18pt; 
        font-weight: bold; 
        color: #800000; /* Dark Red */
        margin: 10px 0; 
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .exam-name-header { text-align: center; font-size: 14pt; font-weight: bold; margin-bottom: 15px; }

    /* Student Info & Grading Table Container */
    .info-grading-container { display: flex; justify-content: space-between; margin-bottom: 10px; align-items: flex-start; }
    
    .student-info { flex: 1; font-size: 11pt; line-height: 1.6; }
    .student-info table { width: 100%; border: none; }
    .student-info td { vertical-align: top; padding: 2px 0; }
    .student-info .label { width: 140px; font-weight: normal; }
    .student-info .colon { width: 15px; }
    .student-info .value { font-weight: bold; font-style: italic; }

    .grading-table { width: 220px; border-collapse: collapse; font-size: 9pt; margin-left: 20px; }
    .grading-table th, .grading-table td { border: 1px solid #000; padding: 2px; text-align: center; }
    .grading-table th { background-color: #f0f0f0; }

    /* Main Result Table */
    .result-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
    .result-table th, .result-table td { border: 1px solid #000; padding: 5px; text-align: center; vertical-align: middle; }
    .result-table th { background-color: #f9f9f9; font-weight: bold; }
    .result-table .text-left { text-align: left; padding-left: 8px; }
    .result-table .sub-name { font-weight: bold; }
    
    /* Footer */
    .footer-section { margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-end; font-size: 11pt; }
    .signature-box { text-align: center; width: 200px; }
    .signature-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; font-weight: bold; }
    
    .date-publication { margin-top: 20px; font-size: 10pt; font-weight: bold; }
    
    @media print {
        .no-print { display: none !important; }
        body { -webkit-print-color-adjust: exact; }
    }
</style>
@endpush

@php
    function getGradePoint($marks, $fullMark) {
        if ($fullMark <= 0) return 0;
        $percent = ($marks / $fullMark) * 100;
        if ($percent >= 80) return 5.00;
        if ($percent >= 70) return 4.00;
        if ($percent >= 60) return 3.50;
        if ($percent >= 50) return 3.00;
        if ($percent >= 40) return 2.00;
        if ($percent >= 33) return 1.00;
        return 0.00;
    }
    
    function getLetterGrade($gp) {
        if ($gp >= 5.00) return 'A+';
        if ($gp >= 4.00) return 'A';
        if ($gp >= 3.50) return 'A-';
        if ($gp >= 3.00) return 'B';
        if ($gp >= 2.00) return 'C';
        if ($gp >= 1.00) return 'D';
        return 'F';
    }

    // Determine Optional Subject Logic (From Controller/Result)
    // We need to identify which subject in $finalSubjects is the fourth subject for this student.
    // The controller sets `fourth_subject_id` on the student/result object or passed it?
    // In printMarksheet, we don't have the full tabulation collection loop, but we can re-derive it.
    
    $studentOptionalSubject = \App\Models\StudentSubject::whereHas('enrollment', function($q) use($student, $exam) {
            $q->where('student_id', $student->id)->where('academic_year_id', $exam->academic_year_id);
    })->where('is_optional', true)->first();
    
    $optionalSubjectId = $studentOptionalSubject ? $studentOptionalSubject->subject_id : null;
    
    // Calculate values
    $mainSubjects = collect();
    $optionalSubject = null;
    $totalGP = 0;
    $totalMainSubjects = 0;
    $optionalGP = 0;
    $isFail = false;
    
    foreach($finalSubjects as $key => $fSub) {
        // Skip display only individual parts for calculation (we show them, but don't count them as subjects)
        if (!empty($fSub['display_only'])) continue;

        // Calculate marks for this subject
        $subTotal = 0;
        $subCreative = 0;
        $subMcq = 0;
        $subPractical = 0;
        $hasMark = false;
        $isAbsent = false;

        $componentIds = $fSub['component_ids'] ?? [$fSub['subject_id']]; // fallback
        
        // Pass Logic
        $isFailedSub = false;
        $totalCreativePass = 0;
        $totalMcqPass = 0;
        $totalPracticalPass = 0;
        $groupHasEachPassType = false;

        foreach ($componentIds as $eid) {
            $m = $marks->firstWhere('exam_subject_id', $eid);
            $subComp = $examSubjects->firstWhere('id', $eid);
            
            if ($subComp) {
                $totalCreativePass += $subComp->creative_pass_mark;
                $totalMcqPass += $subComp->mcq_pass_mark;
                $totalPracticalPass += $subComp->practical_pass_mark;
                if ($subComp->pass_type === 'each') $groupHasEachPassType = true;
            }

            if ($m) {
                $hasMark = true;
                if ($m->is_absent) $isAbsent = true;
                $subTotal += $m->total_marks;
                $subCreative += $m->creative_marks;
                $subMcq += $m->mcq_marks;
                $subPractical += $m->practical_marks;
            }
        }
        
        // Determine GP & Grade
        $gp = 0.00;
        $grade = 'F';
        
        if ($groupHasEachPassType) {
            if ($totalCreativePass > 0 && $subCreative < $totalCreativePass) $isFailedSub = true;
            if ($totalMcqPass > 0 && $subMcq < $totalMcqPass) $isFailedSub = true;
            if ($totalPracticalPass > 0 && $subPractical < $totalPracticalPass) $isFailedSub = true;
        }

        if (!$hasMark) {
            $grade = 'X'; // Not Record
        } elseif ($isAbsent) {
            $grade = 'Abs'; $isFailedSub = true;
        } elseif ($isFailedSub || ($fSub['total_pass_mark'] > 0 && $subTotal < $fSub['total_pass_mark'])) {
            $grade = 'F'; $isFailedSub = true;
        } else {
            $gp = getGradePoint($subTotal, $fSub['total_full_mark']);
            $grade = getLetterGrade($gp);
            if ($grade == 'F') $isFailedSub = true;
        }

        $isOpt = false;
        // Check if any component is the optional subject
        foreach ($componentIds as $cid) {
             $comp = $examSubjects->firstWhere('id', $cid);
             if ($comp && $comp->subject_id == $optionalSubjectId) {
                 $isOpt = true; break;
             }
        }

        $subData = [
            'name' => $fSub['name'],
            'creative' => $subCreative ?: '-',
            'mcq' => $subMcq ?: '-',
            'practical' => $subPractical ?: '-',
            'total' => $hasMark && !$isAbsent ? $subTotal : ($isAbsent ? 'Abs' : '-'),
            'gp' => $hasMark && !$isAbsent ? number_format($gp, 1) : '-',
            'grade' => $grade,
            'is_failed' => $isFailedSub,
            'is_absent' => $isAbsent
        ];

        if ($isOpt) {
            $optionalSubject = $subData;
            $optionalGP = $gp;
        } else {
            $mainSubjects->push($subData);
            if ($isFailedSub) $isFail = true;
            $totalGP += $gp;
            $totalMainSubjects++;
        }
    }

    // Final GPA Calculation
    // Logic: (Total GP of Main Subjects + max(0, OptionalGP - 2)) / Total Main Subjects
    $finalGPA = 0.00;
    if ($isFail) {
        $finalGPA = 0.00;
    } else {
        $optionalBonus = ($optionalGP > 2.0) ? ($optionalGP - 2.0) : 0;
        if ($totalMainSubjects > 0) {
            $finalGPA = ($totalGP + $optionalBonus) / $totalMainSubjects;
        }
    }
    // Cap at 5.00
    if ($finalGPA > 5.00) $finalGPA = 5.00;
    $finalGrade = ($isFail) ? 'F' : getLetterGrade($finalGPA);

@endphp

@section('content')
<div class="print-content">
    
    <!-- Header -->
    <div class="header-section">
        <div class="serial-no">Serial No. {{ $result->id ?? sprintf('%06d', rand(1000,99999)) }}</div> <!-- Placeholder for real serial if exists -->
        <h1>{{ $school->name }}</h1>
        <h2>{{ $school->address }}</h2>
        
        @if($school->logo)
            <img src="{{ asset('storage/'.$school->logo) }}" class="header-logo" alt="Logo">
        @else
             <!-- Placeholder space -->
             <div style="height:80px"></div>
        @endif

        <div class="transcript-title">ACADEMIC TRANSCRIPT</div>
        <div class="exam-name-header">{{ $exam->name }} - {{ $exam->academicYear->name ?? '' }}</div>
    </div>

    <!-- Info & Grading -->
    <div class="info-grading-container">
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">Name of Student</td><td class="colon">:</td>
                    <td class="value">{{ $student->student_name_en }}</td>
                </tr>
                <tr>
                    <td class="label">Father's Name</td><td class="colon">:</td>
                    <td class="value">{{ $student->father_name_en }}</td>
                </tr>
                <tr>
                    <td class="label">Mother's Name</td><td class="colon">:</td>
                    <td class="value">{{ $student->mother_name_en }}</td>
                </tr>
                <tr>
                    <td class="label">Institute</td><td class="colon">:</td>
                    <td class="value">{{ $school->name }} ({{ $school->code ?? '12345' }})</td>
                </tr>
                <tr>
                    <td class="label">Roll Number</td><td class="colon">:</td>
                    <td class="value">{{ $student->currentEnrollment->roll_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Group</td><td class="colon">:</td>
                    <td class="value">{{ $student->currentEnrollment->group->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Date of Birth</td><td class="colon">:</td>
                    <td class="value">{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d/m/Y') : '-' }}</td>
                </tr>
            </table>
        </div>

        <table class="grading-table">
            <thead>
                <tr>
                    <th>Class Interval</th>
                    <th>Letter Grade</th>
                    <th>Grade Point</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>80-100</td><td>A+</td><td>5.00</td></tr>
                <tr><td>70-79</td><td>A</td><td>4.00</td></tr>
                <tr><td>60-69</td><td>A-</td><td>3.50</td></tr>
                <tr><td>50-59</td><td>B</td><td>3.00</td></tr>
                <tr><td>40-49</td><td>C</td><td>2.00</td></tr>
                <tr><td>33-39</td><td>D</td><td>1.00</td></tr>
                <tr><td>0-32</td><td>F</td><td>0.00</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Results Table -->
    <table class="result-table">
        <thead>
            <tr>
                <th style="width: 40px;">Sl. No.</th>
                <th class="text-left">Name of Subjects</th>
                <th style="width: 40px;">CQ</th>
                <th style="width: 40px;">MCQ</th>
                <th style="width: 40px;">PR</th>
                <th style="width: 50px;">Total</th>
                <th style="width: 50px;">Letter Grade</th>
                <th style="width: 50px;">Grade Point</th>
                <th style="width: 60px;">GPA<br><small>(W/O Addl)</small></th>
                <th style="width: 60px;">GPA</th>
            </tr>
        </thead>
        <tbody>
            @php $sl = 1; @endphp
            @foreach($mainSubjects as $index => $sub)
                <tr>
                    <td>{{ $sl++ }}</td>
                    <td class="text-left sub-name">{{ $sub['name'] }}</td>
                    <td>{{ $sub['creative'] }}</td>
                    <td>{{ $sub['mcq'] }}</td>
                    <td>{{ $sub['practical'] }}</td>
                    <td>{{ $sub['total'] }}</td>
                    <td>{{ $sub['grade'] }}</td>
                    <td>{{ $sub['gp'] }}</td>
                    
                    @if($index === 0)
                        <td rowspan="{{ count($mainSubjects) }}" style="vertical-align: middle;">
                            <!-- GPA Without Additional (Approximate or Raw Average) -->
                             {{ $totalMainSubjects > 0 ? number_format($totalGP / $totalMainSubjects, 2) : '0.00' }}
                        </td>
                        <td rowspan="{{ count($mainSubjects) + ($optionalSubject ? 1 : 0) + 1 }}" style="vertical-align: middle; font-weight: bold; font-size: 14pt;">
                            {{ number_format($finalGPA, 2) }}
                        </td>
                    @endif
                </tr>
            @endforeach

            <!-- Additional Subject -->
            @if($optionalSubject)
                <tr>
                    <td colspan="9" class="text-left" style="background-color: #f0f0f0; font-weight: bold; padding-left: 10px;">Additional Subject:</td>
                    <!-- Only close GPA rowspan if not already handled, but structure implies row matches columns. 
                         The GPA column rowspans cover this row. 
                         But wait, the columns count is: Sl+Name+CQ+MCQ+PR+Tot+LG+GP+GPA1+GPA2 = 10 cols.
                         This row has colspan 9? No, Sl..GP is 8 cols. GPA1 is 9th. GPA2 is 10th.
                         Ideally: "Additional Subject" header row? Or just list it? 
                         The transcript image shows "Additional Subject" list item basically.
                    -->
                </tr>
                 <tr>
                    <td>{{ $sl++ }}</td>
                    <td class="text-left sub-name">{{ $optionalSubject['name'] }}</td>
                    <td>{{ $optionalSubject['creative'] }}</td>
                    <td>{{ $optionalSubject['mcq'] }}</td>
                    <td>{{ $optionalSubject['practical'] }}</td>
                    <td>{{ $optionalSubject['total'] }}</td>
                    <td>{{ $optionalSubject['grade'] }}</td>
                    <td>{{ $optionalSubject['gp'] }}</td>
                    <td style="background-color: #eee;">GP Above 2</td>
                </tr>
                <tr>
                    <td colspan="8" class="text-right" style="padding-right: 10px;"><b>GP Above 2.00:</b></td>
                    <td>{{ ($optionalGP > 2) ? number_format($optionalGP - 2.0, 2) : '0.00' }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Footer Signatures -->
    <div class="footer-section">
        <div class="signature-box">
            <div class="signature-line">Compared by</div>
        </div>
        <div class="signature-box">
             <!-- Empty space for signature -->
             <div style="height: 30px;"></div>
             <div class="signature-line">Controller of Examinations</div>
        </div>
    </div>
    
    <div class="date-publication">
        Date of Publication of Result : {{ $result->published_at ? \Carbon\Carbon::parse($result->published_at)->format('d F, Y') : date('d F, Y') }}
    </div>

</div>
@endsection
