@extends('layouts.print')
@section('title', 'Admit Card V1 - ' . $school->name)

@section('suppress_header')@endsection

@php
    $lang = request('lang', 'en'); 
    
    if (!function_exists('dateToWords')) {
        function dateToWords($date) {
            if (!$date) return '';
            $d = date('d', strtotime($date));
            $m = date('F', strtotime($date));
            $y = date('Y', strtotime($date));
            
            $days = [
                1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifth',
                6 => 'Sixth', 7 => 'Seventh', 8 => 'Eighth', 9 => 'Ninth', 10 => 'Tenth',
                11 => 'Eleventh', 12 => 'Twelfth', 13 => 'Thirteenth', 14 => 'Fourteenth', 15 => 'Fifteenth',
                16 => 'Sixteenth', 17 => 'Seventeenth', 18 => 'Eighteenth', 19 => 'Nineteenth', 20 => 'Twentieth',
                21 => 'Twenty First', 22 => 'Twenty Second', 23 => 'Twenty Third', 24 => 'Twenty Fourth', 25 => 'Twenty Fifth',
                26 => 'Twenty Sixth', 27 => 'Twenty Seventh', 28 => 'Twenty Eighth', 29 => 'Twenty Ninth', 30 => 'Thirtieth', 31 => 'Thirty First'
            ];
            
            $years = [
                2000 => 'Two Thousand', 2001 => 'Two Thousand and One', 2002 => 'Two Thousand and Two', 
                2003 => 'Two Thousand and Three', 2004 => 'Two Thousand and Four', 2005 => 'Two Thousand and Five',
                2006 => 'Two Thousand and Six', 2007 => 'Two Thousand and Seven', 2008 => 'Two Thousand and Eight',
                2009 => 'Two Thousand and Nine', 2010 => 'Two Thousand and Ten', 2011 => 'Two Thousand and Eleven',
                2012 => 'Two Thousand and Twelve', 2013 => 'Two Thousand and Thirteen', 2014 => 'Two Thousand and Fourteen',
                2015 => 'Two Thousand and Fifteen', 2016 => 'Two Thousand and Sixteen', 2017 => 'Two Thousand and Seventeen',
                2018 => 'Two Thousand and Eighteen', 2019 => 'Two Thousand and Nineteen', 2020 => 'Two Thousand and Twenty',
                2021 => 'Two Thousand and Twenty One', 2022 => 'Two Thousand and Twenty Two', 2023 => 'Two Thousand and Twenty Three',
                2024 => 'Two Thousand and Twenty Four', 2025 => 'Two Thousand and Twenty Five'
            ];
            
            $dayWord = $days[(int)$d] ?? $d;
            $yearWord = $years[(int)$y] ?? $y;
            
            return $dayWord . ' ' . $m . ' ' . $yearWord;
        }
    }

    if (!function_exists('capitalizeEachWord')) {
        function capitalizeEachWord($str) {
            return ucwords(strtolower($str));
        }
    }

    $bg_setting = \App\Models\Setting::where('school_id', $school->id)->where('key', 'admit_card_v1_background')->first();
    $bg_url = $bg_setting ? asset('storage/' . $bg_setting->value) : asset('assets/institute/admit_card_v1_bg.png');

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
    $p_name = $principal ? $principal->full_name : 'Principal Name';
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
    @page {
        size: A4;
        margin: 0;
    }
    body {
        margin: 0;
        padding: 0;
        font-family: 'Times New Roman', Times, serif !important;
        background: #f0f0f0;
    }
    .admit-card-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10mm 0;
    }
    .admit-card {
        width: 188mm; 
        height: 278mm; 
        background: url({{ $bg_url }});
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-color: #fff;
        position: relative;
        box-sizing: border-box;
        page-break-after: always;
        overflow: hidden;
    }
    .admit-card-body {
        padding: 8mm 10mm 10mm 12mm; /* Reduced padding further */
        height: 100%;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
    }
    .logo {
        text-align: center;
        margin-top: 1mm;
        margin-bottom: 1mm;
    }
    .logo img {
        height: 20mm;
    }
    .head {
        text-align: center;
        margin-bottom: 2mm;
    }
    .head h1 {
        font-size: 18pt;
        margin: 0 0 1mm 0;
        color: #000;
        font-weight: bold;
        text-transform: capitalize;
    }
    .head h2 {
        color: #006600;
        font-size: 20pt;
        margin: 0 0 1mm 0;
        font-weight: bold;
    }
    .head h3 {
        margin: 0;
        font-size: 14pt;
        font-weight: bold;
    }
    .student_photo {
        position: absolute;
        top: 43mm; /* Moved down to prevent overlapping school name */
        right: 15mm;
        border: 1px solid #000;
        background: #fff;
        z-index: 10;
        width: 34mm;
        height: 40mm;
    }
    .student_photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .info {
        margin-top: 1mm;
    }
    .info table {
        width: 100%;
        font-size: 12pt;
        border-collapse: collapse;
    }
    .info table td {
        padding: 0.5mm 0; 
        vertical-align: top;
    }
    .info table th {
        text-align: left;
        font-weight: bold;
    }
    .info table th span {
        border: 2px solid #000;
        padding: 1mm 3mm;
        display: inline-block;
        min-width: 18mm;
        text-align: center;
    }
    .sub {
        margin-top: 2.5mm; 
        flex-grow: 1; 
    }
    .sub table {
        border-collapse: collapse;
        width: 100%;
        font-size: 10.5pt;
    }
    .sub tr th {
        background: #f2f2f2;
        padding: 1.2mm;
        border: 1px solid #999;
        font-weight: bold;
        text-align: center;
        white-space: nowrap; /* Prevent Sl. No. and Sub Code from wrapping */
    }
    .sub td {
        border: 1px solid #999;
        padding: 1mm 2mm; 
        line-height: 1.1;
    }
    /* Restoring Row Colors from Original Code */
    .sub tbody tr:nth-child(odd) td {
        background-color: rgba(255, 0, 0, 0.03);
    }
    .sub tbody tr:nth-child(even) td {
        background-color: rgba(255, 165, 0, 0.03);
    }
    .sub tbody tr.empty-row td {
        border-color: transparent !important;
        background-color: transparent !important;
    }
    .sub tr td:nth-child(1) { text-align: center; width: 12mm; }
    .sub tr td:nth-child(2) { text-align: center; width: 18mm; }
    .sub tr td:nth-child(4), .sub tr td:nth-child(5) { text-align: center; width: 30mm; }

    .footer {
        margin-top: auto;
        padding-top: 1mm;
    }
    .footer-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 2mm;
    }
    .commenced {
        font-weight: bold;
        font-size: 11pt;
    }
    .signature-box {
        text-align: center;
        width: 50%;
    }
    .signature-box img {
        height: 12mm;
        margin-bottom: 1mm;
    }
    .signature-box h4 {
        margin: 0;
        font-size: 11pt;
        font-weight: bold;
    }
    .directions {
        font-size: 9.5pt;
        line-height: 1.2;
        border-top: 1px solid #aaa;
        padding-top: 1.5mm;
    }
    
    b, strong, th {
        font-weight: bold !important;
    }

    @media print {
        body { background: none; }
        .admit-card-container { padding: 0 !important; }
        .admit-card { 
            margin: 5mm auto !important;
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
            $group = $enrollment && $enrollment->group ? $enrollment->group->name : 'N/A';
            $session = $exam->academicYear ? $exam->academicYear->title : date('Y');
            
            $assigned = [];
            $optional_subjects = []; // subject_id => bool
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

            // Commencement Date: Date of the first exam in list
            $firstExam = collect($sched_for_student)->whereNotNull('exam_date')->sortBy('exam_date')->first();
            $commence_date = ($firstExam && $firstExam->exam_date) ? date('d F, Y', strtotime($firstExam->exam_date)) : ($exam->start_date ? $exam->start_date->format('d F, Y') : 'N/A');

            $publicExamData = null;
            if ($exam->public_exam_id && $exam->publicExam) {
                $publicExamData = $student->publicExams->where('exam_name', $exam->publicExam->short_name)->first();
            }

            $display_reg_no = $student->student_id ?: 'N/A';
            $display_session = $session;
            $display_candidate_type = 'Regular';
            $display_centre = ' ';
            
            if ($exam->public_exam_id) {
                if ($publicExamData) {
                    $display_reg_no = $publicExamData->reg_no ?: '';
                    $display_session = $publicExamData->session ?: '';
                    $display_candidate_type = $publicExamData->candidate_type ?: '';
                    $display_centre = $publicExamData->center_name ?: '';
                } else {
                    $display_reg_no = '';
                    $display_session = '';
                    $display_candidate_type = '';
                    $display_centre = '';
                }
            }
        @endphp
        <div class="admit-card">
            <div class="admit-card-body">
                <div class="student_photo">
                    <img src="{{ $student->photo_url }}" onerror="this.src='{{ asset('images/default-avatar.svg') }}'" />
                </div>
                <div class="logo">
                     <img src="{{ $school->logo ? asset('storage/' . $school->logo) : asset('images/batighor_eims.png') }}" alt="" />
                </div>
                <div class="head">
                    <h1>{{ capitalizeEachWord($school->name) }}</h1>
                    <h2>Admit Card</h2>
                    <h3>{{ $exam->name }}</h3>
                </div>
                <div class="info">
                    <table>
                        <tr>
                            <td style="width: 38mm;">Name of Student</td><td style="width: 3mm;">:</td><th colspan="6"><i><b>{{ capitalizeEachWord($student->student_name_en ?: $student->full_name) }}</b></i></th>
                        </tr>
                        <tr>
                            <td>Father's Name</td><td>:</td><th colspan="6"><i><b>{{ capitalizeEachWord($student->father_name) }}</b></i></th>
                        </tr>
                        <tr>
                            <td>Mother's Name</td><td>:</td><th colspan="6"><i><b>{{ capitalizeEachWord($student->mother_name) }}</b></i></th>
                        </tr>
                        <tr>
                            <td>Date of Birth</td><td>:</td><th colspan="6"><i><b>{{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '-' }}</b></i></th>
                        </tr>
                        <tr>
                            <td>( In Word )</td><td>:</td><th colspan="6"><b>{{ dateToWords($student->date_of_birth) }}</b></th>
                        </tr>
                        <tr>
                            <td>Gender</td><td>:</td><th style="width: 35mm;"><b>{{ capitalizeEachWord($student->gender) }}</b></th>
                            <td style="width: 25mm;">Group</td><td style="width: 3mm;">:</td><th><b>{{ capitalizeEachWord($group) }}</b></th>
                        </tr>
                        <tr>
                            <td>EIIN</td><td>:</td><th style="width: 35mm;"><b>{{ $school->eiin ?: 'N/A' }}</b></th>
                            <td style="width: 35mm;">Candidate Type</td><td style="width: 3mm;">:</td><th><b>{{ $display_candidate_type }}</b></th>
                        </tr>
                        <tr>
                            <td>Name of School</td><td>:</td><th colspan="6"><b>{{ capitalizeEachWord($school->name) }}</b></th>
                        </tr>
                        <tr>
                            <td>Registration No.</td><td>:</td><th style="width: 45mm;"><b>{{ $display_reg_no }}</b></th>
                            <td style="width: 25mm;">Session</td><td style="width: 3mm;">:</td><th><b>{{ $display_session }}</b></th>
                        </tr>
                        <tr>
                            <td>Roll No.</td><td>:</td><th style="height: 10mm; vertical-align: middle;"><span style="border: 2px solid #000; padding: 1mm 4mm; display: inline-block;"><b>{{ $roll }}</b></span></th>
                            <td>Centre</td><td>:</td><th colspan="3"><b>{{ $display_centre }}</b></th>
                        </tr>
                    </table>
                </div>
                <div class="sub">
                    <table>
                        <thead>
                            <tr>
                                <th>Sl. No.</th>
                                <th>Sub Code</th>
                                <th>Name of Subject</th>
                                <th>Exam Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sn = 1; @endphp
                            @foreach($sched_for_student as $subject)
                                <tr>
                                    <td>{{ $sn++ }}</td>
                                    <td>{{ $subject->subject_code }}</td>
                                    <td>{{ $subject->subject_name }}@if(!empty($subject->is_optional)) <span style="font-size:8pt;color:#555;font-style:italic;">(Optional)</span>@endif</td>
                                    <td>{{ $subject->exam_date ? date('d/m/Y', strtotime($subject->exam_date)) : '' }}</td>
                                    <td>{{ $subject->exam_time ? date('h:i A', strtotime($subject->exam_time)) : '' }}</td>
                                </tr>
                            @endforeach
                            @php $row_count = count($sched_for_student); @endphp
                            @for($i = $row_count; $i < 12; $i++)
                                <tr class="empty-row">
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="footer">
                    <div class="footer-top">
                        <div class="commenced">
                            To be commenced on : <b>{{ $commence_date }}</b>
                        </div>
                        <div class="signature-box">
                            @if($p_signature)
                                <img src="{{ $p_signature }}" alt="Signature" />
                            @else
                                <div style="height: 10mm;"></div>
                            @endif
                            <h4>( <b>{{ capitalizeEachWord($p_name) }}</b> )</h4>
                            {{ $p_designation }}
                        </div>
                    </div>
                    <div class="directions">
                        <b>Directions:</b> <br />
                        1. The examinee must bring the Registration Card along with the Admit Card in the examination hall.<br />
                        2. The examinee must sign in the attendance sheet for each subject in the examination hall, otherwise examinee will be treated as absent in the respective subject(s).
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
