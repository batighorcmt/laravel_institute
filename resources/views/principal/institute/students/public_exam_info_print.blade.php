@extends('layouts.print')

@section('suppress_header', true)

@section('title', 'শিক্ষার্থীর পাবলিক পরীক্ষার তথ্য')

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
        @media print {
            .student-record {
                page-break-after: always;
            }
            .student-record:last-child {
                page-break-after: auto;
            }
        }
        .student-record {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            background: #fff;
        }
        .school-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .school-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .photo-box {
            position: absolute;
            right: 0;
            top: 100px;
            width: 120px;
            height: 140px;
            border: 1px solid #ccc;
            text-align: center;
            padding: 2px;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .info-table th, .info-table td {
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #eee;
            font-size: 14px;
        }
        .info-table th {
            width: 35%;
            background-color: #f9f9f9;
            font-weight: 600;
        }
        .section-title {
            background: #f0f0f0;
            padding: 5px 10px;
            font-weight: bold;
            margin-top: 20px;
            border-left: 5px solid #333;
            font-size: 16px;
        }
        .meta-info {
            text-align: center;
            margin-bottom: 10px;
            font-size: 13px;
            font-style: italic;
            color: #555;
        }
    </style>

    @foreach($students as $student)
        <div class="student-record">
            {{-- School Header for each student (or just first, but usually better per record if meant for separate filing) --}}
            <div class="school-header">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" class="school-logo">
                @endif
                <h1 style="margin: 0; font-size: 24px; font-weight: 800;">{{ $school->name_bn ?? $school->name }}</h1>
                <div style="font-size: 14px;">{{ $school->address_bn ?? $school->address }}</div>
                <h2 style="margin: 10px 0 0 0; font-size: 18px; border: 1px solid #000; display: inline-block; padding: 2px 15px;">{{ $publicExamName }} পরীক্ষার তথ্যাবলী</h2>
            </div>

            <div class="meta-info">
                শিক্ষাবর্ষ: {{ $academicYear->name }} | শ্রেণি: {{ $class->name }}
            </div>

            <div class="photo-box">
                <img src="{{ $student->photo_url }}">
            </div>

            <div class="section-title">ব্যক্তিগত তথ্য</div>
            <table class="info-table">
                <tr>
                    <th>শিক্ষার্থীর নাম (ইংরেজিতে)</th>
                    <td>{{ $student->student_name_en }}</td>
                </tr>
                <tr>
                    <th>শিক্ষার্থীর নাম (বাংলায়)</th>
                    <td>{{ $student->student_name_bn ?: '-' }}</td>
                </tr>
                <tr>
                    <th>পিতার নাম</th>
                    <td>{{ $student->father_name_bn ?: $student->father_name }}</td>
                </tr>
                <tr>
                    <th>মাতার নাম</th>
                    <td>{{ $student->mother_name_bn ?: $student->mother_name }}</td>
                </tr>
                <tr>
                    <th>জন্ম তারিখ</th>
                    <td>{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d-m-Y') : '-' }}</td>
                </tr>
                <tr>
                    <th>শ্রেণির রোল</th>
                    <td>{{ optional($student->enrollments->first())->roll_no }}</td>
                </tr>
                <tr>
                    <th>ষ্টুডেন্ট আইডি</th>
                    <td>{{ $student->student_id }}</td>
                </tr>
            </table>

            @php
                $pe = $student->publicExams->first();
            @endphp

            <div class="section-title">পাবলিক পরীক্ষার তথ্য ({{ $publicExamName }})</div>
            <table class="info-table">
                <tr>
                    <th>শিক্ষা বোর্ড</th>
                    <td>{{ $pe->board ?? '-' }}</td>
                </tr>
                <tr>
                    <th>পাবলিক পরীক্ষার রোল নং</th>
                    <td>{{ $pe->roll_no ?? '-' }}</td>
                </tr>
                <tr>
                    <th>রেজিস্ট্রেশন নং</th>
                    <td>{{ $pe->reg_no ?? '-' }}</td>
                </tr>
                <tr>
                    <th>পরীক্ষার বছর</th>
                    <td>{{ $pe->exam_year ?? '-' }}</td>
                </tr>
                <tr>
                    <th>সেশন</th>
                    <td>{{ $pe->session ?? '-' }}</td>
                </tr>
                <tr>
                    <th>পরীক্ষার্থীর ধরণ (Type)</th>
                    <td>{{ $pe->candidate_type ?? '-' }}</td>
                </tr>
                <tr>
                    <th>পরীক্ষা কেন্দ্রের নাম</th>
                    <td>{{ $pe->center_name ?? '-' }}</td>
                </tr>
            </table>

            <div style="margin-top: 60px; display: flex; justify-content: space-between;">
                <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px; font-size: 13px;">
                    শ্রেণি শিক্ষকের স্বাক্ষর
                </div>
                <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px; font-size: 13px;">
                    অধ্যক্ষ/প্রধান শিক্ষকের স্বাক্ষর
                </div>
            </div>

            <div style="margin-top: 20px; font-size: 10px; color: #888; text-align: right;">
                Print date: {{ date('d-m-Y h:i A') }}
            </div>
        </div>
    @endforeach
@endsection
