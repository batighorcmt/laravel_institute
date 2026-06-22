@extends('layouts.print')
@section('title', 'Admit Card V5 - ' . $school->name)

@section('suppress_header')@endsection

@php
    $lang = request('lang', 'en'); 
    
    if (!function_exists('capitalizeEachWord')) {
        function capitalizeEachWord($str) {
            return ucwords(strtolower($str));
        }
    }

    if (!function_exists('enToBnNumber')) {
        function enToBnNumber($number) {
            $en = ['0','1','2','3','4','5','6','7','8','9','AM','PM','am','pm'];
            $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯','এএম','পিএম','এএম','পিএম'];
            return str_replace($en, $bn, $number);
        }
    }

    $__ = function($enStr, $bnStr) use ($lang) {
        return $lang === 'bn' ? $bnStr : $enStr;
    };

    // Fetch Principal Info
    $principalPivot = \App\Models\UserSchoolRole::where('school_id', $school->id)
        ->whereHas('role', function($q){ $q->where('name', 'principal'); })
        ->first();
    $principal = null;
    if ($principalPivot) {
        $principal = \App\Models\Teacher::where('school_id', $school->id)
            ->where('user_id', $principalPivot->user_id)
            ->first();
    }
    
    $p_name = 'Principal Name';
    if ($principal) {
        if ($lang === 'bn' && $principal->full_name_bn) {
            $p_name = $principal->full_name_bn;
        } else {
            $p_name = $principal->full_name;
        }
    }
    
    $p_designation = $principal ? $principal->designation : 'Principal / Head Teacher';
    $p_signature = null;
    
    if ($principal && $principal->signature) {
        if (file_exists(storage_path('app/public/' . $principal->signature))) {
             $p_signature = asset('storage/' . ltrim($principal->signature, '/'));
        } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists('teachers/' . $principal->signature)) {
             $p_signature = asset('storage/teachers/' . $principal->signature);
        }
    }
@endphp

@push('print_head')
<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Noto+Serif+Bengali:wght@400;500;600;700&display=swap');

    @page {
        size: A4;
        margin: 0;
    }
    body {
        margin: 0;
        padding: 0;
        font-family: 'Outfit', 'Noto Serif Bengali', sans-serif !important;
        background: #f4f6f9;
        color: #333;
    }
    .admit-card-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10mm 0;
    }
    .admit-card {
        width: 190mm; 
        height: auto; 
        min-height: 277mm;
        background-color: #ffffff;
        position: relative;
        box-sizing: border-box;
        page-break-after: always;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }

    .admit-card-body {
        padding: 8mm 10mm 8mm 10mm;
        height: 100%;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        position: relative;
        z-index: 1;
    }

    /* Header Section */
    .header-section {
        display: flex;
        align-items: center;
        border-bottom: 2px solid #5e72e4;
        padding-bottom: 3mm;
        margin-bottom: 3mm;
    }
    .logo {
        width: 22mm;
        height: 22mm;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .logo img {
        max-width: 100%;
        max-height: 100%;
    }
    .school-info {
        flex: 1;
        text-align: center;
        padding: 0 3mm;
        overflow: hidden; /* To prevent expanding past bounds */
    }
    .school-info h1 {
        font-size: 16pt; /* Fits in one line */
        margin: 0 0 2px 0;
        color: #172b4d;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .school-info p {
        margin: 0;
        font-size: 10pt;
        color: #525f7f;
    }
    .school-info .admit-title {
        display: inline-block;
        background: #5e72e4;
        color: white;
        padding: 3px 15px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 12pt;
        margin-top: 4px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* Exam Title */
    .exam-title-section {
        text-align: center;
        margin-bottom: 4mm;
    }
    .exam-title-section h2 {
        margin: 0;
        font-size: 14pt;
        color: #32325d;
        font-weight: 700;
    }

    /* Student Info Section */
    .student-info-section {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4mm;
        background: #f8f9fe;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 3mm 4mm;
    }
    .info-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5pt;
    }
    .info-table td {
        padding: 2px 0;
        vertical-align: middle;
        color: #32325d;
    }
    .info-table td.label {
        font-weight: 600;
        width: 35mm;
        color: #525f7f;
    }
    .info-table td.colon {
        width: 3mm;
        font-weight: 600;
    }
    .info-table td.value {
        font-weight: 700;
    }

    /* Smaller photo size */
    .student-photo-wrapper {
        width: 26mm;
        height: 33mm;
        margin-left: 4mm;
        flex-shrink: 0;
        border: 2px solid #e9ecef;
        border-radius: 4px;
        padding: 2px;
        background: #fff;
    }
    .student-photo-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 2px;
    }

    /* Seat Plan Badge */
    .seat-plan-badge {
        background: #2dce89;
        color: white;
        border-radius: 6px;
        padding: 4px 15px;
        display: flex;
        justify-content: space-around;
        align-items: center;
        margin-bottom: 4mm;
    }
    .seat-item {
        text-align: center;
    }
    .seat-item .s-label {
        font-size: 8.5pt;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.95;
    }
    .seat-item .s-val {
        font-size: 14pt;
        font-weight: 700;
        line-height: 1.2;
    }

    /* Routine Table */
    .routine-section {
        flex-grow: 1;
    }
    .routine-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
        border-radius: 4px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }
    .routine-table thead {
        background: #f6f9fc;
    }
    .routine-table th {
        padding: 6px;
        text-align: center;
        color: #525f7f;
        font-weight: 600;
        border: 1px solid #e9ecef;
        text-transform: uppercase;
        font-size: 8.5pt;
    }
    .routine-table td {
        padding: 4px 6px;
        border: 1px solid #e9ecef;
        color: #32325d;
        font-weight: 500;
    }
    .routine-table tbody tr:nth-child(even) {
        background-color: #fcfcfd;
    }
    .routine-table td:nth-child(1),
    .routine-table td:nth-child(2) {
        text-align: center;
    }
    .routine-table td:nth-child(4),
    .routine-table td:nth-child(5) {
        text-align: center;
        font-weight: 600;
    }

    .bottom-wrapper {
        margin-top: auto;
        display: flex;
        flex-direction: column;
    }
    
    /* Footer / Signatures */
    .footer-section {
        padding-top: 6mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    .signature-block {
        text-align: center;
        width: 45%;
    }
    .signature-block img {
        height: 10mm;
        margin-bottom: 2px;
    }
    .signature-line {
        border-top: 1px dashed #32325d;
        padding-top: 3px;
        font-size: 10pt;
        font-weight: 600;
        color: #32325d;
    }
    .directions {
        margin-top: 3mm;
        font-size: 8.5pt;
        color: #525f7f;
        border-top: 1px solid #e9ecef;
        padding-top: 3px;
        line-height: 1.4;
    }

    @media print {
        body { background: none; }
        .admit-card-container { padding: 0 !important; }
        .admit-card { 
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
            height: 277mm;
        }
    }
</style>
@endpush

@section('content')
<div class="admit-card-container">
    @foreach($students as $student)
        @php
            $enrollment = $student->enrollments->first();
            $roll = $enrollment ? $enrollment->roll_no : '';
            $roll = str_pad($roll, 6, '0', STR_PAD_LEFT);
            $groupName = $enrollment && $enrollment->group ? ($lang === 'bn' && $enrollment->group->bangla_name ? $enrollment->group->bangla_name : $enrollment->group->name) : 'N/A';
            $sectionNameStr = $enrollment && $enrollment->section ? ($lang === 'bn' && $enrollment->section->bangla_name ? $enrollment->section->bangla_name : $enrollment->section->name) : 'N/A';
            
            // Correct Class Translation Logic
            $classDisplay = $exam->class ? ($lang === 'bn' && $exam->class->bangla_name ? $exam->class->bangla_name : $exam->class->name) : 'N/A';
            
            // Seat Plan Data
            $allocation = isset($seatPlanAllocations) ? $seatPlanAllocations->get($student->id) : null;
            $roomNo = $allocation && $allocation->room ? $allocation->room->room_no : 'N/A';
            $colNo = $allocation ? $allocation->col_no : 'N/A';
            $benchNo = $allocation ? $allocation->bench_no : 'N/A';
            $position = $allocation ? $allocation->position : 'N/A';

            $assigned = [];
            $optional_subjects = [];
            if (!empty($assigned_by_student[$student->id])) {
                foreach ($assigned_by_student[$student->id] as $subId => $info) {
                    $assigned[] = $subId;
                    $optional_subjects[$subId] = is_array($info) ? ($info['is_optional'] ?? false) : false;
                }
            }

            $sched_for_student = [];
            if (!empty($assigned)) {
                foreach ($schedule as $row) {
                    if (isset($row->subject_id) && in_array(intval($row->subject_id), $assigned, true)) {
                        $r = clone $row;
                        $r->is_optional = $optional_subjects[intval($row->subject_id)] ?? false;
                        $sched_for_student[] = $r;
                    }
                }
            } else {
                $sched_for_student = collect($schedule)->map(function($row) {
                    $r = clone $row;
                    $r->is_optional = false;
                    return $r;
                })->all();
            }
            
            $display_board_reg_no = $student->board_registration_no ?: 'N/A';
        @endphp
        
        <div class="admit-card">
            <div class="admit-card-body">
                
                <!-- Header Section -->
                <div class="header-section">
                    <div class="logo">
                        <img src="{{ $school->logo ? asset('storage/' . $school->logo) : asset('images/batighor_eims.png') }}" alt="School Logo" />
                    </div>
                    <div class="school-info">
                        <h1>{{ $lang === 'bn' && $school->name_bn ? $school->name_bn : capitalizeEachWord($school->name) }}</h1>
                        <p>{{ $lang === 'bn' && $school->address_bn ? $school->address_bn : $school->address }}</p>
                        <div class="admit-title">{{ $__('ADMIT CARD', 'প্রবেশপত্র') }}</div>
                    </div>
                    <div class="logo" style="visibility: hidden;">
                        <!-- Spacer to balance the header -->
                    </div>
                </div>

                <!-- Exam Title -->
                <div class="exam-title-section">
                    <h2>{{ $lang === 'bn' && $exam->name_bn ? $exam->name_bn : $exam->name }}</h2>
                </div>

                <!-- Student Info Section -->
                <div class="student-info-section">
                    <div style="flex: 1;">
                        <table class="info-table">
                            <tr>
                                <td class="label">{{ $__('Name of Student', 'শিক্ষার্থীর নাম') }}</td><td class="colon">:</td><td class="value" colspan="4" style="text-transform: uppercase;">{{ $lang === 'bn' && $student->student_name_bn ? $student->student_name_bn : ($student->student_name_en ?: $student->full_name) }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $__('Class', 'শ্রেণি') }}</td><td class="colon">:</td><td class="value">{{ $classDisplay }}</td>
                                <td class="label" style="width: 20mm; padding-left: 10px;">{{ $__('Section', 'শাখা') }}</td><td class="colon">:</td><td class="value">{{ $sectionNameStr }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $__('Roll No.', 'রোল নম্বর') }}</td><td class="colon">:</td><td class="value" style="font-size: 12pt; color: #5e72e4;">{{ $lang === 'bn' ? enToBnNumber($roll) : $roll }}</td>
                                <td class="label" style="padding-left: 10px;">{{ $__('Group', 'বিভাগ') }}</td><td class="colon">:</td><td class="value">{{ $lang === 'bn' ? $groupName : capitalizeEachWord($groupName) }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $__('Student ID', 'শিক্ষার্থী আইডি') }}</td><td class="colon">:</td><td class="value">{{ $student->student_id ?: 'N/A' }}</td>
                                <td class="label" style="padding-left: 10px;">{{ $__('Gender', 'লিঙ্গ') }}</td><td class="colon">:</td><td class="value">{{ $lang === 'bn' ? ($student->gender == 'Male' ? 'ছাত্র' : ($student->gender == 'Female' ? 'ছাত্রী' : 'অন্যান্য')) : capitalizeEachWord($student->gender) }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $__('Board Reg. No.', 'বোর্ড রেজিঃ নং') }}</td><td class="colon">:</td><td class="value" colspan="4">{{ $display_board_reg_no !== 'N/A' ? ($lang === 'bn' ? enToBnNumber($display_board_reg_no) : $display_board_reg_no) : 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="student-photo-wrapper">
                        <img src="{{ $student->photo_url }}" onerror="this.src='{{ asset('images/default-avatar.svg') }}'" />
                    </div>
                </div>

                <!-- Seat Plan Info -->
                <div class="seat-plan-badge">
                    <div class="seat-item">
                        <div class="s-label">{{ $__('Room Number', 'রুম নম্বর') }}</div>
                        <div class="s-val">{{ $roomNo !== 'N/A' ? ($lang === 'bn' ? enToBnNumber($roomNo) : $roomNo) : 'N/A' }}</div>
                    </div>
                    @php
                        $colLabel = 'N/A';
                        if ($colNo !== 'N/A') {
                            $colInt = intval($colNo);
                            if ($lang === 'bn') {
                                $colLabel = $colInt == 1 ? 'বাম সারি' : ($colInt == 2 ? 'মধ্য সারি' : ($colInt == 3 ? 'ডান সারি' : enToBnNumber($colNo) . ' সারি'));
                            } else {
                                $colLabel = $colInt == 1 ? 'Left Row' : ($colInt == 2 ? 'Middle Row' : ($colInt == 3 ? 'Right Row' : $colNo . ' Row'));
                            }
                        }
                    @endphp
                    <div class="seat-item">
                        <div class="s-label">{{ $__('Row', 'সারি') }}</div>
                        <div class="s-val" style="font-size:11pt;">{{ $colLabel }}</div>
                    </div>
                    <div class="seat-item">
                        <div class="s-label">{{ $__('Bench', 'বেঞ্চ') }}</div>
                        <div class="s-val">{{ $benchNo !== 'N/A' ? ($lang === 'bn' ? enToBnNumber($benchNo) : $benchNo) : 'N/A' }}</div>
                    </div>
                    @php
                        $positionLabel = 'N/A';
                        if ($position !== 'N/A') {
                            $posStr = strtolower(trim($position));
                            if ($lang === 'bn') {
                                $positionLabel = ($posStr === 'left' || $posStr === '1') ? 'বাম দিক' : (($posStr === 'right' || $posStr === '3') ? 'ডান দিক' : (($posStr === 'middle' || $posStr === 'center' || $posStr === '2') ? 'মধ্য দিক' : $position));
                            } else {
                                $positionLabel = ($posStr === 'left' || $posStr === '1') ? 'Left' : (($posStr === 'right' || $posStr === '3') ? 'Right' : (($posStr === 'middle' || $posStr === 'center' || $posStr === '2') ? 'Middle' : ucfirst($position)));
                            }
                        }
                    @endphp
                    <div class="seat-item">
                        <div class="s-label">{{ $__('Position', 'অবস্থান') }}</div>
                        <div class="s-val" style="font-size:11pt;">{{ $positionLabel }}</div>
                    </div>
                </div>

                <!-- Exam Routine -->
                <div class="routine-section">
                    <table class="routine-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">{{ $__('Sl. No.', 'ক্র. নং') }}</th>
                                <th style="width: 15%;">{{ $__('Sub Code', 'বিষয় কোড') }}</th>
                                <th style="width: 45%; text-align: left;">{{ $__('Name of Subject', 'বিষয়ের নাম') }}</th>
                                <th style="width: 15%;">{{ $__('Exam Date', 'পরীক্ষার তারিখ') }}</th>
                                <th style="width: 15%;">{{ $__('Time', 'সময়') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sn = 1; @endphp
                            @foreach($sched_for_student as $subject)
                                <tr>
                                    <td>{{ $lang === 'bn' ? enToBnNumber(str_pad($sn, 2, '0', STR_PAD_LEFT)) : str_pad($sn, 2, '0', STR_PAD_LEFT) }}</td>
                                    @php $sn++; @endphp
                                    <td>{{ $subject->subject_code ? ($lang === 'bn' ? enToBnNumber($subject->subject_code) : $subject->subject_code) : '' }}</td>
                                    <td>
                                        {{ $lang === 'bn' && $subject->subject_bangla_name ? $subject->subject_bangla_name : $subject->subject_name }}
                                        @if(!empty($subject->is_optional)) 
                                            <span style="font-size:8pt; color:#8898aa; font-style:italic;">({{ $__('Optional', 'ঐচ্ছিক') }})</span>
                                        @endif
                                    </td>
                                    <td>{{ $subject->exam_date ? ($lang === 'bn' ? enToBnNumber(date('d/m/Y', strtotime($subject->exam_date))) : date('d/M/Y', strtotime($subject->exam_date))) : '-' }}</td>
                                    <td>{{ $subject->exam_time ? ($lang === 'bn' ? enToBnNumber(date('h:i A', strtotime($subject->exam_time))) : date('h:i A', strtotime($subject->exam_time))) : '-' }}</td>
                                </tr>
                            @endforeach
                            @php $row_count = count($sched_for_student); @endphp
                            @for($i = $row_count; $i < max(4, $row_count); $i++)
                                <tr>
                                    <td style="color: transparent;">-</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="bottom-wrapper">
                    <!-- Signatures & Footer -->
                    <div class="footer-section">
                        <div class="signature-block">
                            <div style="height: 10mm;"></div>
                            <div class="signature-line">{{ $__('Signature of Class Teacher', 'শ্রেণি শিক্ষকের স্বাক্ষর') }}</div>
                        </div>
                        <div class="signature-block">
                            @if($p_signature)
                                <img src="{{ $p_signature }}" alt="Signature" />
                            @else
                                <div style="height: 10mm;"></div>
                            @endif
                            <div class="signature-line">{{ $p_name }}<br><span style="font-size:8pt;font-weight:400;">{{ $p_designation }}</span></div>
                        </div>
                    </div>

                    <div class="directions">
                        <strong>{{ $__('Instructions:', 'নির্দেশনাবলী:') }}</strong> 
                        @if($lang === 'bn')
                            ১. পরীক্ষা কক্ষে অবশ্যই এই প্রবেশপত্র সাথে আনতে হবে। 
                            ২. মোবাইল ফোন বা যেকোনো ইলেকট্রনিক ডিভাইস আনা সম্পূর্ণ নিষেধ। 
                            ৩. প্রতিটি বিষয়ের জন্য হাজিরা শিটে স্বাক্ষর করতে হবে, অন্যথায় অনুপস্থিত বলে গণ্য করা হবে।
                        @else
                            1. Bring this Admit Card to the examination hall. 
                            2. Mobile phones or electronic devices are strictly prohibited. 
                            3. Sign the attendance sheet for each subject, otherwise you will be marked absent.
                        @endif
                    </div>
                </div>

            </div>
        </div>
    @endforeach
</div>
@endsection
