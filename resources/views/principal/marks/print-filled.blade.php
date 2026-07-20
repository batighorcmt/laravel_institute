@extends('layouts.print')

@php
  $lang = request('lang','bn');

  $printTitle = $lang==='bn' ? 'বিষয়ভিত্তিক মার্কশিট ফরম (পূর্ণ)' : 'Subject-wise Marksheet Form (Filled)';
  $examInfo = $lang==='bn' ?
    'পরীক্ষা: ' . ($exam->name_bn ?: $exam->name) . ' | শ্রেণি: ' . ($exam->class->bangla_name ?: $exam->class->name) :
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
      <strong>{{ $lang==='bn' ? 'বিষয়:' : 'Subject:' }}</strong> {{ $lang==='bn' ? ($examSubject->subject->bangla_name ?: $examSubject->subject->name) : $examSubject->subject->name }}
      @if($examSubject->subject->code)
        <strong>({{ $examSubject->subject->code }})</strong>
      @endif
    </div>
    <div>
      <strong>{{ $lang==='bn' ? 'শিক্ষক:' : 'Teacher:' }}</strong>
      @if($examSubject->teacher)
        @php
          $tName = $examSubject->teacher->name;
          if($lang==='bn' && $examSubject->teacher->teacher && trim($examSubject->teacher->teacher->full_name_bn)) {
            $tName = $examSubject->teacher->teacher->full_name_bn;
          }
        @endphp
        {{ $tName }}
      @else
        {{ $lang==='bn' ? 'অনির্ধারিত' : 'Not Assigned' }}
      @endif
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
            @php 
                $decimal = \App\Models\Setting::getDecimalPosition($school->id); 
            @endphp
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
                            @php $val = $mark && !$mark->is_absent ? number_format($mark->creative_marks, $decimal, '.', '') : ''; @endphp
                            {{ $val ? ($lang==='bn' ? toBengaliNumber($val) : $val) : '' }}
                        </td>
                    @endif

                    @if($examSubject->mcq_full_mark > 0)
                        <td style="text-align: center;">
                            @php $val = $mark && !$mark->is_absent ? number_format($mark->mcq_marks, $decimal, '.', '') : ''; @endphp
                            {{ $val ? ($lang==='bn' ? toBengaliNumber($val) : $val) : '' }}
                        </td>
                    @endif

                    @if($examSubject->practical_full_mark > 0)
                        <td style="text-align: center;">
                            @php $val = $mark && !$mark->is_absent ? number_format($mark->practical_marks, $decimal, '.', '') : ''; @endphp
                            {{ $val ? ($lang==='bn' ? toBengaliNumber($val) : $val) : '' }}
                        </td>
                    @endif

                    <td style="text-align: center;">
                        @php $val = $mark && !$mark->is_absent ? number_format($mark->total_marks, $decimal, '.', '') : ''; @endphp
                        {{ $val ? ($lang==='bn' ? toBengaliNumber($val) : $val) : '' }}
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

    @php
        $totalStudents = $students->count();
        $absentCount = 0;
        $passCount = 0;
        $failCount = 0;
        $gradeCounts = [];

        foreach ($students as $student) {
            $mark = $marks->get($student->id);
            if (!$mark) {
                continue;
            }

            if ($mark->is_absent) {
                $absentCount++;
                $gradeCounts['F'] = ($gradeCounts['F'] ?? 0) + 1;
                continue;
            }

            $grade = $mark->letter_grade ?? '-';
            $gradeCounts[$grade] = ($gradeCounts[$grade] ?? 0) + 1;

            if ($grade === 'F') {
                $failCount++;
            } else {
                $passCount++;
            }
        }

        $gradeOrder = ['A+', 'A', 'A-', 'B', 'C', 'D', 'F'];
        uksort($gradeCounts, function ($a, $b) use ($gradeOrder) {
            $posA = array_search($a, $gradeOrder);
            $posB = array_search($b, $gradeOrder);
            $posA = $posA === false ? count($gradeOrder) : $posA;
            $posB = $posB === false ? count($gradeOrder) : $posB;
            return $posA <=> $posB ?: strcmp($a, $b);
        });
    @endphp

    <div class="print-summary" style="margin-top: 25px;">
        <div style="font-weight: bold; margin-bottom: 8px; font-size: 14px;">
            {{ $lang==='bn' ? 'ফলাফলের সারাংশ' : 'Result Summary' }}
        </div>
        <table class="print-table summary-table" style="width: auto; min-width: 60%;">
            <tbody>
                <tr>
                    <th>{{ $lang==='bn' ? 'মোট শিক্ষার্থী' : 'Total Students' }}</th>
                    <td>{{ $lang==='bn' ? toBengaliNumber($totalStudents) : $totalStudents }}</td>
                    <th>{{ $lang==='bn' ? 'উপস্থিত' : 'Present' }}</th>
                    <td>{{ $lang==='bn' ? toBengaliNumber($totalStudents - $absentCount) : $totalStudents - $absentCount }}</td>
                    <th>{{ $lang==='bn' ? 'অনুপস্থিত' : 'Absent' }}</th>
                    <td>{{ $lang==='bn' ? toBengaliNumber($absentCount) : $absentCount }}</td>
                </tr>
                <tr>
                    <th>{{ $lang==='bn' ? 'পাস' : 'Pass' }}</th>
                    <td>{{ $lang==='bn' ? toBengaliNumber($passCount) : $passCount }}</td>
                    <th>{{ $lang==='bn' ? 'ফেল' : 'Fail' }}</th>
                    <td>{{ $lang==='bn' ? toBengaliNumber($failCount) : $failCount }}</td>
                    <th>{{ $lang==='bn' ? 'পাসের হার' : 'Pass Rate' }}</th>
                    <td>
                        @php
                            $passRate = $totalStudents > 0 ? round(($passCount / $totalStudents) * 100, 2) : 0;
                        @endphp
                        {{ $lang==='bn' ? toBengaliNumber($passRate) : $passRate }}%
                    </td>
                </tr>
            </tbody>
        </table>

        @if(count($gradeCounts) > 0)
            <div style="font-weight: bold; margin: 12px 0 6px; font-size: 14px;">
                {{ $lang==='bn' ? 'গ্রেডভিত্তিক সংখ্যা' : 'Grade-wise Count' }}
            </div>
            <table class="print-table summary-table" style="width: auto; min-width: 60%;">
                <thead>
                    <tr>
                        @foreach($gradeCounts as $grade => $count)
                            <th style="text-align: center;">{{ $grade }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach($gradeCounts as $grade => $count)
                            <td style="text-align: center;">{{ $lang==='bn' ? toBengaliNumber($count) : $count }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

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

    .print-summary {
        page-break-inside: avoid;
    }

    .summary-table th {
        background: #f0f0f0;
        text-align: center;
    }

    .summary-table td {
        text-align: center;
    }

    @media print {
        body {
            font-size: 14px;
        }

        .subject-info {
            page-break-inside: avoid;
        }

        .print-table {
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
            min-height: 15px;
            padding: 2px 4px;
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
