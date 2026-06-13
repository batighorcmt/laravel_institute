@extends('layouts.print')

@section('title', 'আইডি কার্ড প্রিন্ট')

@section('suppress_header')@endsection
@section('suppress_watermark')@endsection

@section('content')
@php
    $settingsArr = is_string($settings) ? json_decode($settings, true) : json_decode(json_encode($settings), true);
    $settings = is_array($settingsArr) ? $settingsArr : [];
    $fields = $settings['fields'] ?? ['class', 'roll', 'section'];
    $lang = $settings['language'] ?? 'bn';

    // Labels
    $labels = [
        'class'        => $lang === 'en' ? 'Class'       : 'শ্রেণি',
        'section'      => $lang === 'en' ? 'Section'     : 'শাখা',
        'group'        => $lang === 'en' ? 'Group'       : 'গ্রুপ',
        'roll'         => $lang === 'en' ? 'Roll No.'    : 'রোল',
        'reg_no'       => $lang === 'en' ? 'Reg. No.'    : 'রেজি নং',
        'session'      => $lang === 'en' ? 'Session'     : 'সেশন',
        'dob'          => $lang === 'en' ? 'DOB'         : 'জন্ম তারিখ',
        'blood_group'  => $lang === 'en' ? 'Blood Group' : 'রক্তের গ্রুপ',
        'father_name'  => $lang === 'en' ? 'Father'      : 'পিতার নাম',
        'mother_name'  => $lang === 'en' ? 'Mother'      : 'মাতার নাম',
        'mobile'       => $lang === 'en' ? 'Mobile'      : 'মোবাইল',
        'student_id'   => $lang === 'en' ? 'ID No.'      : 'আইডি নং',
    ];

    // Bengali number helper
    $enToBn = function($str) use ($lang) {
        if ($lang !== 'bn') return $str;
        return str_replace(['0','1','2','3','4','5','6','7','8','9'],
                           ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], $str);
    };

    // Title case helper
    $titleCase = function($str) use ($lang) {
        if ($lang !== 'en' || !$str) return $str;
        return \Illuminate\Support\Str::title($str);
    };
@endphp

<style>
    @page {
        size: A4;
        margin: 5mm;
    }
    body {
        margin: 0;
        padding: 0;
        background: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .print-header { display: none !important; }

    .id-card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 3mm;
        justify-content: flex-start;
        align-content: flex-start;
    }

    .id-card {
        border: 0.3px solid #ccc;
        position: relative;
        overflow: hidden;
        background-color: #fff;
        background-repeat: no-repeat;
        background-size: 100% 100%;
        box-sizing: border-box;
        page-break-inside: avoid;
        break-inside: avoid;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .card-content {
        height: 100%;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .student-photo {
        object-fit: cover;
        display: block;
        background: #fff;
        flex-shrink: 0;
    }

    .student-name {
        font-weight: bold;
        text-align: center;
        width: 100%;
        display: block;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }
    .info-table td {
        vertical-align: top;
        padding: 0.2mm 0;
        line-height: 1.3;
    }
    .info-table .label {
        white-space: nowrap;
        width: 38%;
    }
    .info-table .value {
        padding-left: 1.5mm;
        word-break: break-word;
    }
</style>

<div class="id-card-container">
    @foreach($students as $student)
        @php
            $enrollment = $student->enrollments->first();
            $data = [
                'class'       => $lang === 'bn'
                                    ? $enToBn($enrollment->class->bangla_name ?: $enrollment->class->name)
                                    : ($enrollment->class->name ?? '-'),
                'section'     => $lang === 'bn'
                                    ? $enToBn($enrollment->section->bangla_name ?: $enrollment->section->name)
                                    : ($enrollment->section->name ?? '-'),
                'group'       => $enrollment->group->name ?? '-',
                'roll'        => $enToBn($enrollment->roll_no ?? '-'),
                'reg_no'      => $enToBn($student->reg_no ?? '-'),
                'session'     => $enToBn($enrollment->academic_year->name ?? '-'),
                'dob'         => $enToBn($student->dob ?? '-'),
                'blood_group' => $student->blood_group ?? '-',
                'father_name' => $lang === 'en' ? $titleCase($student->father_name) : $student->father_name_bn,
                'mother_name' => $lang === 'en' ? $titleCase($student->mother_name) : $student->mother_name_bn,
                'mobile'      => $enToBn($student->mobile ?? '-'),
                'student_id'  => $student->student_id ?? '-',
            ];

            $studentName = $lang === 'en'
                ? $titleCase($student->student_name_en ?: $student->student_name_bn)
                : ($student->student_name_bn ?: $student->student_name_en);
        @endphp

        <div class="id-card" style="
            width: {{ $settings['card_width'] }}mm;
            height: {{ $settings['card_height'] }}mm;
            padding-top: {{ $settings['content_padding_top'] }}mm;
            padding-left: {{ $settings['margin_left'] }}mm;
            padding-right: {{ $settings['margin_right'] }}mm;
            padding-bottom: {{ $settings['margin_bottom'] }}mm;
            {{ $settings['background_image'] ? 'background-image: url('.$settings['background_image'].');' : '' }}
        ">
            <div class="card-content">
                {{-- ফটো --}}
                <img src="{{ $student->photo_url }}"
                     class="student-photo"
                     style="width: {{ $settings['photo_width'] }}mm; height: {{ $settings['photo_height'] }}mm; margin-bottom: {{ ($settings['row_spacing'] ?? 1.5) }}mm;">

                {{-- নাম --}}
                <div class="student-name" style="font-size: {{ $settings['name_font_size'] }}pt; color: {{ $settings['name_color'] }}; margin-bottom: {{ ($settings['row_spacing'] ?? 1.5) }}mm;">
                    {{ $studentName }}
                </div>

                {{-- তথ্য তালিকা --}}
                <table class="info-table" style="
                    font-size: {{ $settings['details_font_size'] }}pt;
                    color: {{ $settings['details_color'] }};
                ">
                    @foreach($fields as $f)
                        @if(isset($labels[$f]))
                            <tr @if($f === 'student_id') style="font-size: {{ $settings['id_no_font_size'] ?? $settings['details_font_size'] }}pt; color: {{ $settings['id_no_color'] ?? $settings['details_color'] }}; font-weight: bold;" @endif>
                                <td class="label">{{ $labels[$f] }}:</td>
                                <td class="value">{{ $data[$f] ?? '-' }}</td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection