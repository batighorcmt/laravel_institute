@extends('layouts.print')

@php
  $lang = request('lang','bn');

  // Function to convert English numbers to Bengali
  function toBengaliNumber($number) {
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($englishDigits, $bengaliDigits, (string)$number);
  }

  $printTitle = $lang==='bn' ? 'বিষয়ভিত্তিক মার্কশিট ফরম (খালি)' : 'Subject-wise Marksheet Form (Blank)';
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
                    <th width="15%">{{ $lang==='bn' ? 'সৃজনশীল' : 'Creative' }} ({{ $lang==='bn' ? toBengaliNumber($examSubject->creative_full_mark) : $examSubject->creative_full_mark }})</th>
                @endif
                @if($examSubject->mcq_full_mark > 0)
                    <th width="15%">{{ $lang==='bn' ? 'MCQ' : 'MCQ' }} ({{ $lang==='bn' ? toBengaliNumber($examSubject->mcq_full_mark) : $examSubject->mcq_full_mark }})</th>
                @endif
                @if($examSubject->practical_full_mark > 0)
                    <th width="15%">{{ $lang==='bn' ? 'ব্যবহারিক' : 'Practical' }} ({{ $lang==='bn' ? toBengaliNumber($examSubject->practical_full_mark) : $examSubject->practical_full_mark }})</th>
                @endif
                <th width="10%">{{ $lang==='bn' ? 'মোট' : 'Total' }}</th>
                <th width="8%">{{ $lang==='bn' ? 'গ্রেড' : 'Grade' }}</th>
                <th width="7%">{{ $lang==='bn' ? 'অনুপস্থিত' : 'Absent' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enrollment)
                <tr>
                    <td style="text-align: center;">{{ $lang==='bn' ? toBengaliNumber($loop->iteration) : $loop->iteration }}</td>
                    <td style="text-align: center;">{{ $lang==='bn' ? toBengaliNumber($enrollment->roll_no) : $enrollment->roll_no }}</td>
                    <td>{{ $lang==='bn' ? ($enrollment->student->student_name_bn ?: $enrollment->student->student_name_en) : $enrollment->student->student_name_en }}</td>

                    @if($examSubject->creative_full_mark > 0)
                        <td style="text-align: center; min-height: 30px;"></td>
                    @endif

                    @if($examSubject->mcq_full_mark > 0)
                        <td style="text-align: center; min-height: 30px;"></td>
                    @endif

                    @if($examSubject->practical_full_mark > 0)
                        <td style="text-align: center; min-height: 30px;"></td>
                    @endif

                    <td style="text-align: center; min-height: 30px;"></td>
                    <td style="text-align: center; min-height: 30px;"></td>
                    <td style="text-align: center; min-height: 30px;"></td>
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
            font-size: 11px;
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
            font-size: 10px;
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
