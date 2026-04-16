@extends('layouts.print')

@php $lang = request('lang', 'en'); @endphp

@section('title', $publicExamName . ' Exam Information — Table View')

@section('content')
    @php
        $logoUrl = asset('images/default-logo.png');
        if(isset($school) && $school && $school->logo){
            $candidates = ['uploads/schools/'.$school->logo, 'storage/schools/'.$school->logo, 'storage/'.$school->logo];
            foreach($candidates as $c){
                if(file_exists(public_path($c))){ $logoUrl = asset($c); break; }
            }
        }
    @endphp

    <style>
        /* ── Page & Layout ── */
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 12mm 8mm;
        }

        /* Override layout's header border so it can be shown below the title instead */
        .print-header {
            border-bottom: none !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        /* ── Custom Header (Bangla + English) ── */
        .table-print-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 8px;
            position: relative;
        }
        .table-print-header .logo-left {
            position: absolute;
            left: 0;
            top: 0;
            width: 65px;
            height: 65px;
        }
        .table-print-header .logo-left img {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }
        .table-print-header .bn-name {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
        }
        .table-print-header .bn-addr {
            font-size: 13px;
            margin: 1px 0 0 0;
            font-weight: 500;
        }
        .table-print-header .en-name {
            margin: 2px 0 0 0;
            font-size: 17px;
            font-weight: 700;
            font-style: italic;
            line-height: 1.1;
        }
        .table-print-header .en-addr {
            font-size: 12px;
            margin: 1px 0 0 0;
            font-style: italic;
        }
        .table-print-header .exam-title {
            display: inline-block;
            margin: 6px 0 0 0;
            font-size: 16px;
            font-weight: 700;
            border: 1.5px solid #000;
            padding: 2px 18px;
        }
        .meta-line {
            text-align: center;
            font-size: 12px;
            color: #444;
            margin-bottom: 6px;
        }

        /* ── Table ── */
        .exam-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            line-height: 1.3;
        }
        .exam-table th,
        .exam-table td {
            border: 1px solid #555;
            padding: 4px 5px;
            vertical-align: middle;
            text-align: center;
        }
        .exam-table thead th {
            background: #e8e8e8;
            font-weight: 700;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }
        .exam-table tbody td {
            font-size: 11px;
        }
        .exam-table .text-left {
            text-align: left !important;
        }
        .exam-table .photo-cell img {
            width: 40px;
            height: 48px;
            object-fit: cover;
            border: 1px solid #ccc;
        }
        .exam-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        /* ── Print-specific ── */
        @media print {
            body {
                font-size: 11px;
            }
            .exam-table {
                page-break-inside: auto;
            }
            .exam-table thead {
                display: table-header-group;
            }
            .exam-table tr {
                page-break-inside: avoid !important;
            }
            .exam-table tbody tr:nth-child(even) {
                background: #fafafa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .exam-table thead th {
                background: #e8e8e8 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .footer-line {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }
        .footer-line .sign-block {
            text-align: center;
            width: 180px;
            border-top: 1px solid #000;
            padding-top: 4px;
        }
        .print-date {
            text-align: right;
            font-size: 9px;
            color: #888;
            margin-top: 8px;
        }
    </style>

    {{-- Header --}}
    <div class="table-print-header" style="margin-top: 10px;">
        <div class="exam-title">{{ $publicExamName }} Examination Information</div>
    </div>

    <div class="meta-line">
        Academic Year: {{ $academicYear->name }} &nbsp;|&nbsp; Class: {{ $class->name }}
        &nbsp;|&nbsp; Total Students: {{ $students->count() }}
    </div>

    {{-- Table --}}
    <table class="exam-table">
        <thead>
            <tr>
                <th style="width:28px;">SL</th>
                <th>Photo</th>
                <th>Student ID</th>
                <th class="text-left" style="min-width:110px;">Student Name</th>
                <th class="text-left" style="min-width:100px;">Father's Name</th>
                <th class="text-left" style="min-width:100px;">Mother's Name</th>
                <th style="width:72px;">Date of Birth</th>
                <th>Board</th>
                <th>Exam Roll</th>
                <th>Reg. No.</th>
                <th>Exam Year</th>
                <th>Session</th>
                <th>Type</th>
                <th style="min-width:80px;">Centre</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $idx => $student)
                @php
                    $enrollment = $student->enrollments->first();
                    $pe = $student->publicExams->first();
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="photo-cell">
                        <img src="{{ $student->photo_url }}" alt="photo">
                    </td>
                    <td>{{ $student->student_id }}</td>
                    <td class="text-left">{{ $student->student_name_en ?: $student->student_name_bn }}</td>
                    <td class="text-left">{{ $student->father_name ?: '-' }}</td>
                    <td class="text-left">{{ $student->mother_name ?: '-' }}</td>
                    <td>{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d-m-Y') : '-' }}</td>
                    <td>{{ $pe->board ?? '-' }}</td>
                    <td>{{ $pe->roll_no ?? '-' }}</td>
                    <td>{{ $pe->reg_no ?? '-' }}</td>
                    <td>{{ $pe->exam_year ?? '-' }}</td>
                    <td>{{ $pe->session ?? '-' }}</td>
                    <td>{{ $pe->candidate_type ?? '-' }}</td>
                    <td>{{ $pe->center_name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer-line">
        <div class="sign-block">Class Teacher's Signature</div>
        <div class="sign-block">Principal / Head Teacher's Signature</div>
    </div>

    <div class="print-date">Print Date: {{ date('d-m-Y h:i A') }}</div>
@endsection
