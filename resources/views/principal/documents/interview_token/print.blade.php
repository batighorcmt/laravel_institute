@extends('layouts.print')

@section('title', 'অভিভাবক সাক্ষাৎকার টোকেন')

@section('suppress_header')@endsection
@section('suppress_watermark')@endsection

@push('print_head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
@endpush

@section('content')
@php
    $enToBn = function ($str) {
        if ($str === null || $str === '') return $str;
        return str_replace(
            ['0','1','2','3','4','5','6','7','8','9'],
            ['০','১','২','৩','৪','৫','৬','৭','৮','৯'],
            (string) $str
        );
    };
    $schoolName = $school->name_bn ?: $school->name;
    $className  = $class->bangla_name ?: $class->name;
    $interviewDate = request('interview_date')
        ? $enToBn(\Carbon\Carbon::parse(request('interview_date'))->format('d/m/Y'))
        : null;
@endphp

<style>
    @page { size: A4; margin: 8mm; }
    body { margin: 0; padding: 0; background: none !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .print-header { display: none !important; }

    .token-container {
        font-family: 'Hind Siliguri', 'Kalpurush', Arial, sans-serif;
        display: flex;
        flex-wrap: wrap;
        gap: 14mm 12mm;
        justify-content: flex-start;
        align-content: flex-start;
    }

    .token-card {
        width: 58mm;
        min-height: 42mm;
        border: 1px solid #b9c2cb;
        border-radius: 3mm;
        box-sizing: border-box;
        overflow: hidden;
        position: relative;
        background: #fff;
        box-shadow: 0 1px 3px rgba(20,30,60,0.10);
        page-break-inside: avoid;
        break-inside: avoid;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .token-head {
        background: linear-gradient(135deg, #12543a, #1f7a52);
        color: #fff;
        padding: 2.4mm 3mm 2mm;
        text-align: center;
    }

    .token-school-name {
        font-size: 11.5px;
        font-weight: 700;
        line-height: 1.25;
    }

    .token-title {
        font-size: 9px;
        font-weight: 500;
        letter-spacing: 0.3px;
        margin-top: 0.6mm;
        opacity: 0.92;
    }

    .token-body {
        padding: 3mm 3.5mm 2.5mm;
    }

    .token-row {
        font-size: 10.5px;
        margin-bottom: 1.6mm;
        line-height: 1.45;
        color: #22303f;
    }

    .token-row .label {
        color: #5c6b7a;
        font-weight: 600;
        margin-right: 0.8mm;
    }

    .token-row .value {
        font-weight: 700;
        color: #12213a;
    }

    .token-meta {
        display: flex;
        gap: 3mm;
    }

    .token-time-box {
        margin-top: 2mm;
        border: 1px dashed #8a97a3;
        border-radius: 2mm;
        padding: 1.6mm 2mm;
        background: #f7faf8;
    }

    .token-time-box .info-line {
        display: flex;
        align-items: center;
    }

    .token-time-box .info-line + .info-line {
        margin-top: 1.4mm;
    }

    .token-time-box .label {
        font-size: 10px;
        font-weight: 600;
        color: #3d4a56;
        white-space: nowrap;
    }

    .token-time-box .value {
        margin-left: 1.5mm;
        font-size: 10px;
        font-weight: 700;
        color: #12213a;
    }

    .token-time-box .dots {
        flex: 1;
        margin-left: 2mm;
        border-bottom: 1px dotted #5c6b7a;
        min-height: 3mm;
    }

    .token-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 3mm;
        padding-top: 1.5mm;
        border-top: 1px dashed #b9c2cb;
    }

    .token-footer .sign-box {
        min-width: 30mm;
        text-align: center;
    }

    .token-footer .sign-space {
        height: 7mm;
    }

    .token-footer .sign-line {
        font-size: 9.5px;
        font-weight: 600;
        color: #3d4a56;
        padding-top: 1mm;
        border-top: 1px solid #444;
    }
</style>

<div class="token-container">
    @foreach($enrollments as $enrollment)
        @php
            $student = $enrollment->student;
            $studentName = $student->student_name_bn ?: $student->student_name_en;
            $sectionName = $enrollment->section ? ($enrollment->section->bangla_name ?: $enrollment->section->name) : '-';
        @endphp
        <div class="token-card">
            <div class="token-head">
                <div class="token-school-name">{{ $schoolName }}</div>
                <div class="token-title">অভিভাবক সাক্ষাৎকার টোকেন</div>
            </div>
            <div class="token-body">
                <div class="token-row"><span class="label">শিক্ষার্থীর নাম:</span><span class="value">{{ $studentName }}</span></div>
                <div class="token-row token-meta">
                    <span><span class="label">শ্রেণি:</span><span class="value">{{ $className }}</span></span>
                    <span><span class="label">শাখা:</span><span class="value">{{ $sectionName }}</span></span>
                    <span><span class="label">রোল:</span><span class="value">{{ $enToBn($enrollment->roll_no) }}</span></span>
                </div>

                <div class="token-time-box">
                    <div class="info-line">
                        <span class="label">সাক্ষাৎকারের তারিখ:</span>
                        @if($interviewDate)
                            <span class="value">{{ $interviewDate }}</span>
                        @else
                            <span class="dots">&nbsp;</span>
                        @endif
                    </div>
                    <div class="info-line">
                        <span class="label">সাক্ষাৎকারের সময়:</span>
                        <span class="dots">&nbsp;</span>
                    </div>
                </div>

                <div class="token-footer">
                    <div class="sign-box">
                        <div class="sign-space"></div>
                        <div class="sign-line">শ্রেণি শিক্ষকের স্বাক্ষর</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
