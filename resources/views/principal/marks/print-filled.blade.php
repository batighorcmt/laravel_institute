@extends('layouts.print')

@php
  $lang = request('lang','bn');

  // Function to convert English numbers to Bengali
  function toBengaliNumber($number) {
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($englishDigits, $bengaliDigits, (string)$number);
  }

  $printTitle = $lang==='bn' ? 'বিষয়ভিত্তিক মার্কশিট ফরম (পূর্ণ)' : 'Subject-wise Marksheet Form (Filled)';
  $examInfo = $lang==='bn' ?
    'পরীক্ষা: ' . ($exam->name_bn ?: $exam->name) . ' | শ্রেণি: ' . $exam->class->name :
    'Exam: ' . $exam->name . ' | Class: ' . $exam->class->name;
@endphp

@section('title', $printTitle)

@section('print_header_right')
<div style="text-align: center; font-size: 14px; font-weight: bold;">
  {{ $examInfo }}
</div>
@endsection

@section('content')
<div class="subject-info" style="margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <strong>{{ $lang==='bn' ? 'বিষয়:' : 'Subject:' }}</strong> {{ $examSubject->subject->name }}
      @if($examSubject->subject->code)
        <strong>({{ $examSubject->subject->code }})</strong>
      @endif
    </div>
    <div>
      <strong>{{ $lang==='bn' ? 'শিক্ষক:' : 'Teacher:' }}</strong>
      {{ $examSubject->teacher ? ($examSubject->teacher->name ?? '') : ($lang==='bn' ? 'অনির্ধারিত' : 'Not Assigned') }}
    </div>
  </div>
</div>

<div class="print-table-container">
    <table class="print-table">
        <thead>
            <tr>
                <th width="5%">{{ $lang==='bn' ? 'ক্রমিক' : 'Sl' }}</th>
                <th width="10%">{{ $lang==='bn' ? 'রোল' : 'Roll' }}</th>
                <th width="25%">{{ $lang==='bn' ? 'শিক্ষার্থীর নাম' : 'Student Name' }}</th>
                @if($examSubject->creative_full_mark > 0)
                    <th width="12%">{{ $lang==='bn' ? 'সৃজনশীল' : 'Creative' }} ({{ $lang==='bn' ? toBengaliNumber($examSubject->creative_full_mark) : $examSubject->creative_full_mark }})</th>
                @endif
                @if($examSubject->mcq_full_mark > 0)
                    <th width="12%">{{ $lang==='bn' ? 'MCQ' : 'MCQ' }} ({{ $lang==='bn' ? toBengaliNumber($examSubject->mcq_full_mark) : $examSubject->mcq_full_mark }})</th>
                @endif
                @if($examSubject->practical_full_mark > 0)
                    <th width="12%">{{ $lang==='bn' ? 'ব্যবহারিক' : 'Practical' }} ({{ $lang==='bn' ? toBengaliNumber($examSubject->practical_full_mark) : $examSubject->practical_full_mark }})</th>
                @endif
                <th width="10%">{{ $lang==='bn' ? 'মোট' : 'Total' }}</th>
                <th width="8%">{{ $lang==='bn' ? 'গ্রেড' : 'Grade' }}</th>
                <th width="8%">{{ $lang==='bn' ? 'অনুপস্থিত' : 'Absent' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
                @php
                    $mark = $marks->get($student->id);
                    $enrollment = $student->enrollments->first();
                    $rollNo = $enrollment ? $enrollment->roll_no : '';
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $lang==='bn' ? toBengaliNumber($loop->iteration) : $loop->iteration }}</td>
                    <td style="text-align: center;">{{ $lang==='bn' ? toBengaliNumber($rollNo) : $rollNo }}</td>
                    <td>{{ $lang==='bn' ? ($student->student_name_bn ?: $student->student_name_en) : $student->student_name_en }}</td>

                    @if($examSubject->creative_full_mark > 0)
                        <td style="text-align: center;">
                            {{ $mark && !$mark->is_absent ? ($lang==='bn' ? toBengaliNumber($mark->creative_marks) : $mark->creative_marks) : '' }}
                        </td>
                    @endif

                    @if($examSubject->mcq_full_mark > 0)
                        <td style="text-align: center;">
                            {{ $mark && !$mark->is_absent ? ($lang==='bn' ? toBengaliNumber($mark->mcq_marks) : $mark->mcq_marks) : '' }}
                        </td>
                    @endif

                    @if($examSubject->practical_full_mark > 0)
                        <td style="text-align: center;">
                            {{ $mark && !$mark->is_absent ? ($lang==='bn' ? toBengaliNumber($mark->practical_marks) : $mark->practical_marks) : '' }}
                        </td>
                    @endif

                    <td style="text-align: center;">
                        {{ $mark && !$mark->is_absent ? ($lang==='bn' ? toBengaliNumber($mark->total_marks) : $mark->total_marks) : '' }}
                    </td>
                    <td style="text-align: center;">
                        {{ $mark ? ($mark->is_absent ? 'F' : ($mark->letter_grade ?? '')) : '' }}
                    </td>
                    <td style="text-align: center;">
                        {{ $mark && $mark->is_absent ? '✓' : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 12px; text-align: right;">
        <strong>{{ $lang==='bn' ? 'পরীক্ষকের স্বাক্ষর:' : 'Examiner\'s Signature:' }}</strong> ___________________________
    </div>
</div>

@push('print_head')
<style>
    .subject-info {
        page-break-after: avoid;
    }

    .print-table-container {
        margin-top: 20px;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .print-table th,
    .print-table td {
        border: 1px solid #000;
        padding: 6px;
        text-align: left;
        vertical-align: middle;
    }

    .print-table thead th {
        background: #f0f0f0;
        font-weight: bold;
        text-align: center;
        font-size: 14px;
    }

    .print-table tbody td {
        min-height: 25px;
        font-size: 14px;
    }

    @media print {
        body {
            font-size: 14px;
        }

        .subject-info,
        .print-table-container {
            page-break-inside: avoid;
        }

        .print-table {
            page-break-inside: avoid;
            font-size: 14px;
        }

        .print-table th,
        .print-table td {
            padding: 4px;
        }

        .print-table thead th {
            font-size: 14px;
        }

        .print-table tbody td {
            font-size: 14px;
            min-height: 20px;
        }

        .print-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .print-table td {
            page-break-inside: avoid;
        }

        .print-table thead {
            display: table-header-group;
        }
    }
</style>
@endpush
@endsection
