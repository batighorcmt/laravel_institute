@extends('layouts.print')
@section('title', 'Admit Card V4 - ' . $school->name)

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
@endphp

@push('print_head')
<style type="text/css">
    @page {
        size: A4;
        margin: 5mm; /* Added margin to prevent clipping */
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
        width: 190mm; 
        height: 277mm; 
        background: url({{ $bg_url }});
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-color: #fff;
        position: relative;
        box-sizing: border-box;
        page-break-after: always;
        overflow: hidden;
        border: none; /* Removed border */
        padding: 10mm;
        margin-bottom: 5mm;
    }
    
    .admit-card-inner {
        position: relative;
        z-index: 1;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .header-section {
        text-align: center;
        margin-bottom: 3mm;
    }
    .school-logo {
        height: 22mm; /* Balanced logo size */
        margin-bottom: 2mm;
    }
    .school-name {
        font-size: 18pt; 
        font-weight: bold;
        margin: 0;
        color: #000;
        text-transform: uppercase;
        line-height: 1.2;
    }
    .admit-title {
        font-size: 22pt; 
        font-weight: bold;
        color: #008000;
        margin: 1mm 0;
        font-family: 'Georgia', serif;
        line-height: 1.1;
    }
    .exam-name {
        font-size: 16pt; 
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    .student-photo-box {
        position: absolute;
        top: 38mm; 
        right: 5mm;
        width: 32mm; 
        height: 38mm;
        border: 1px solid #000;
        padding: 1px;
        background: #fff;
    }
    .student-photo-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .info-section {
        margin-top: 2mm;
        margin-left: 2mm;
    }
    .info-table {
        width: 82%;
        border-collapse: collapse;
        font-size: 13pt; 
        line-height: 1.1;
    }
    .info-table td {
        padding: 0.6mm 0; /* Balanced spacing */
        vertical-align: top;
    }
    .info-label {
        width: 45mm;
        font-weight: normal;
    }
    .info-separator {
        width: 5mm;
        text-align: center;
    }
    .info-value {
        font-weight: bold;
        font-style: italic;
    }
    
    .box-value {
        border: 1.5px solid #000;
        padding: 0.5mm 3mm;
        display: inline-block;
        min-width: 25mm;
        text-align: center;
        font-style: normal;
        background: #fff;
    }

    .subject-section {
        margin-top: 5mm;
        flex-grow: 1; 
    }
    .subject-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11pt; 
    }
    .subject-table th {
        background-color: #98fb98; 
        border: 1px solid #000;
        padding: 1.5mm;
        font-weight: bold;
    }
    .subject-table td {
        border: 1px solid #000;
        padding: 1mm 1.5mm; 
        background: rgba(255, 255, 255, 0.7);
        line-height: 1.1;
    }
    .subject-table tr:nth-child(even) td {
        background: rgba(220, 235, 255, 0.8); /* Light blue colorful shading */
    }
    .text-center { text-align: center; }

    .footer-section {
        margin-top: auto;
        padding-bottom: 2mm;
        display: flex;
        justify-content: flex-end;
    }
    .signature-area {
        text-align: center;
        width: 60mm;
    }
    .signature-img {
        height: 10mm;
        margin-bottom: -1mm; /* Reduced space between signature and text */
    }
    .sig-line {
        border-top: 1px solid #000;
        margin-top: 0mm; /* Reduced space */
        padding-top: 0.5mm;
    }
    .sig-title {
        font-weight: bold;
        font-size: 11pt;
        margin: 0;
    }

    .directions-section {
        border-top: 1px dashed #000;
        padding-top: 2mm;
        font-size: 9pt;
    }
    .directions-section h4 {
        margin: 0 0 1mm 0;
        text-decoration: underline;
    }
    .directions-section ol {
        margin: 0;
        padding-left: 5mm;
    }

    @media print {
        body { background: none; }
        .admit-card-container { padding: 0 !important; }
    }
</style>
@endpush

@section('content')
<div class="admit-card-container">
    @foreach($students as $student)
        @php
            $enrollment = $student->enrollments->first();
            $roll = $enrollment ? $enrollment->roll_no : '';
            $group = $enrollment && $enrollment->group ? $enrollment->group->name : 'N/A';
            
            $assigned = [];
            if (!empty($assigned_by_student[$student->id])) {
                foreach ($assigned_by_student[$student->id] as $subId => $info) {
                    $assigned[] = $subId;
                }
            }

            $sched_for_student = [];
            if (!empty($assigned)) {
                foreach ($schedule as $row) {
                    if (isset($row->subject_id) && in_array(intval($row->subject_id), $assigned, true)) {
                        $sched_for_student[] = $row;
                    }
                }
            } else {
                $sched_for_student = $schedule;
            }

            $publicExamData = null;
            if ($exam->public_exam_id && $exam->publicExam) {
                $publicExamData = $student->publicExams->where('exam_name', $exam->publicExam->short_name)->first();
            }

            $display_roll = str_pad($roll, 6, '0', STR_PAD_LEFT);
            $display_reg_no = $student->board_registration_no ?: ($student->student_id ?: '');
            $display_session = $exam->academicYear ? $exam->academicYear->title : date('Y');
            $display_centre = '';
            $display_board = '';

            if ($publicExamData) {
                $display_roll = $publicExamData->roll_no ?: $display_roll;
                $display_reg_no = $publicExamData->reg_no ?: $display_reg_no;
                $display_session = $publicExamData->session ?: $display_session;
                $display_centre = $publicExamData->center_name ?: '';
                $display_board = $publicExamData->board ?: '';
            }
        @endphp
        <div class="admit-card">
            <div class="admit-card-inner">
                <div class="header-section">
                    <img class="school-logo" src="{{ $school->logo ? asset('storage/' . $school->logo) : asset('images/batighor_eims.png') }}" alt="School Logo">
                    <h1 class="school-name">{{ $school->name }}</h1>
                    <h2 class="admit-title">Admit Card</h2>
                    <h3 class="exam-name">{{ $exam->name }}</h3>
                </div>

                <div class="student-photo-box">
                    <img src="{{ $student->photo_url }}" onerror="this.src='{{ asset('images/default-avatar.svg') }}'" />
                </div>

                <div class="info-section">
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Board</td>
                            <td class="info-separator">:</td>
                            <td class="info-value"><span class="box-value">{{ $display_board ?: 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td class="info-label">Centre Code & Name</td>
                            <td class="info-separator">:</td>
                            <td class="info-value">{{ $display_centre ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">EIIN & School Name</td>
                            <td class="info-separator">:</td>
                            <td class="info-value">{{ $school->eiin }} - {{ $school->name }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Name of Student</td>
                            <td class="info-separator">:</td>
                            <td class="info-value">{{ capitalizeEachWord($student->student_name_en ?: $student->full_name) }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Father's Name</td>
                            <td class="info-separator">:</td>
                            <td class="info-value">{{ capitalizeEachWord($student->father_name) }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Mother's Name</td>
                            <td class="info-separator">:</td>
                            <td class="info-value">{{ capitalizeEachWord($student->mother_name) }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date of Birth</td>
                            <td class="info-separator">:</td>
                            <td class="info-value">
                                {{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '-' }} 
                                <span style="font-style: normal; font-weight: normal; margin-left: 2mm;">( {{ dateToWords($student->date_of_birth) }} )</span>
                            </td>
                        </tr>
                    </table>
                    
                    <table class="info-table" style="width: 100%; margin-top: 1.5mm;">
                        <tr>
                            <td class="info-label">Version</td>
                            <td class="info-separator">:</td>
                            <td class="info-value" style="width: 40mm;">Bangla</td>
                            <td class="info-label" style="width: 25mm;">Gender</td>
                            <td class="info-separator">:</td>
                            <td class="info-value">{{ capitalizeEachWord($student->gender) }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Roll No.</td>
                            <td class="info-separator">:</td>
                            <td class="info-value" style="width: 40mm;"><span class="box-value">{{ $display_roll }}</span></td>
                            <td class="info-label" style="width: 35mm;">Registration No.</td>
                            <td class="info-separator">:</td>
                            <td class="info-value"><span class="box-value">{{ $display_reg_no }}</span></td>
                        </tr>
                    </table>
                </div>

                <div class="subject-section">
                    <table class="subject-table">
                        <thead>
                            <tr>
                                <th style="width: 12mm;">Sl. No.</th>
                                <th style="width: 20mm;">Sub Code</th>
                                <th>Name of Subject</th>
                                <th style="width: 28mm;">Exam Date</th>
                                <th style="width: 22mm;">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sn = 1; @endphp
                            @foreach($sched_for_student as $subject)
                                <tr>
                                    <td class="text-center">{{ $sn++ }}</td>
                                    <td class="text-center">{{ $subject->subject_code }}</td>
                                    <td>{{ $subject->subject_name }}</td>
                                    <td class="text-center">{{ $subject->exam_date ? date('d/m/Y', strtotime($subject->exam_date)) : '' }}</td>
                                    <td class="text-center">{{ $subject->exam_time ? date('h:i A', strtotime($subject->exam_time)) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="footer-section">
                    <div class="signature-area">
                        @if($principalTeacher && $principalTeacher->signature)
                             <img class="signature-img" src="{{ asset('storage/' . ltrim($principalTeacher->signature, '/')) }}" alt="Signature">
                        @endif
                        <div class="sig-line">
                            <p class="sig-title">Institution Head's Signature</p>
                        </div>
                    </div>
                </div>

                <div class="directions-section">
                    <h4>Directions:</h4>
                    <ol>
                        <li>The examinee must bring the Admit Card in the examination hall.</li>
                        <li>The examinee must sign in the attendance sheet for each subject in the examination hall, otherwise the examinee will be treated as absent in the respective subject(s).</li>
                    </ol>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
