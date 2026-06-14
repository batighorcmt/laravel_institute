@extends('layouts.print')

@section('title', $printTitle)

@push('print_head')
<style>
    /* ===== Page Setup ===== */
    @page {
        size: A4 landscape;
        margin: 10mm 10mm 16mm 10mm;
    }

    /* Force background colors on print */
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }

    /* ===== Table ===== */
    .rwr-print-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        font-size: 12px;
    }
    .rwr-print-table thead tr {
        background: #1e3a5f !important;
        color: #fff !important;
    }
    .rwr-print-table thead th {
        padding: 7px 8px;
        font-weight: 700;
        text-align: center;
        border: 1px solid #0d2340;
        font-size: 12px;
        white-space: nowrap;
    }
    .rwr-print-table thead th.th-teacher {
        text-align: left;
        min-width: 150px;
    }
    .rwr-print-table tbody tr {
        border-bottom: 1px solid #c8d4e3;
    }
    .rwr-print-table tbody tr:nth-child(even) td {
        background: #f4f7fb !important;
    }
    .td-teacher-print {
        padding: 6px 8px;
        font-weight: 600;
        color: #1e3a5f;
        border: 1px solid #c8d4e3;
        border-right: 2px solid #8aaac8;
        white-space: nowrap;
        vertical-align: middle;
        min-width: 150px;
    }
    .td-teacher-print small {
        display: block;
        font-weight: 400;
        color: #5a7a9e;
        font-size: 10px;
    }
    .td-period-print {
        padding: 5px 6px;
        text-align: center;
        vertical-align: middle;
        border: 1px solid #c8d4e3;
        min-width: 90px;
    }
    .td-period-print.has-data {
        background: #ffffff !important;
    }
    .td-period-print.no-data {
        background: #f0f3f8 !important;
    }
    .pr-cell-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .pr-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 2px;
    }
    /* Subject name */
    .pr-subject {
        font-weight: 700;
        color: #000;
        font-size: 13px;
        display: block;
        line-height: 1.2;
    }
    .pr-meta {
        font-size: 11.5px;
        font-weight: 600;
        color: #222;
        display: block;
        line-height: 1.2;
    }
    /* Status marks */
    .pr-status {
        display: block;
        text-align: center;
        flex-shrink: 0;
    }
    /* Tick — green circle with ✓ */
    .pr-tick {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #16a34a !important;
        color: #fff;
        font-size: 13px;
        font-weight: 900;
        line-height: 20px;
        text-align: center;
    }
    /* Cross — red circle with ✗ */
    .pr-cross {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #c0152d !important;
        color: #fff;
        font-size: 12px;
        font-weight: 900;
        line-height: 20px;
        text-align: center;
    }
    /* Empty dash */
    .pr-empty-dash {
        color: #b0bec5;
        font-size: 13px;
    }

    /* ===== Summary bar ===== */
    .print-summary {
        display: flex;
        gap: 16px;
        margin-bottom: 8px;
        flex-wrap: wrap;
        font-size: 12px;
    }
    .ps-item {
        padding: 4px 12px;
        border-radius: 4px;
        font-weight: 700;
        border: 1px solid #aaa;
    }
    .ps-total  { background: #e3eaf6 !important; color: #1e3a5f; border-color: #8aaac8; }
    .ps-done   { background: #d4edda !important; color: #155724; border-color: #7dc89d; }
    .ps-miss   { background: #f8d7da !important; color: #721c24; border-color: #e8949b; }

    /* ===== Legend ===== */
    .print-legend {
        display: flex;
        align-items: center;
        gap: 14px;
        font-size: 11px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }
    .pl-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
</style>
@endpush

@section('content')
@php
    $totalRoutine = $routineEntries->count();
    $totalEvaled  = $evaluations->count();
    $totalMissing = max(0, $totalRoutine - $totalEvaled);

    // Bangla number helper
    $toBn = function($n) {
        $d = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
        return strtr((string)$n, $d);
    };
@endphp

{{-- Summary bar --}}
<div class="print-summary">
    <span class="ps-item ps-total">
        {{ $lang === 'bn' ? 'মোট পিরিওড: ' . $toBn($totalRoutine) : 'Total Periods: ' . $totalRoutine }}
    </span>
    <span class="ps-item ps-done">
        {{ $lang === 'bn' ? 'ইভ্যালুয়েশন হয়েছে: ' . $toBn($totalEvaled) : 'Evaluated: ' . $totalEvaled }}
    </span>
    <span class="ps-item ps-miss">
        {{ $lang === 'bn' ? 'ইভ্যালুয়েশন হয়নি: ' . $toBn($totalMissing) : 'Not Evaluated: ' . $totalMissing }}
    </span>
</div>

{{-- Legend --}}
<div class="print-legend">
    <strong>{{ $lang === 'bn' ? 'চিহ্নিত করণ:' : 'Legend:' }}</strong>
    <span class="pl-item">
        <span class="pr-tick">✓</span>
        {{ $lang === 'bn' ? 'ইভ্যালুয়েশন হয়েছে' : 'Evaluation done' }}
    </span>
    <span class="pl-item">
        <span class="pr-cross">✗</span>
        {{ $lang === 'bn' ? 'ইভ্যালুয়েশন হয়নি' : 'Not evaluated' }}
    </span>
    <span class="pl-item">
        <span class="pr-empty-dash">—</span>
        {{ $lang === 'bn' ? 'এই পিরিওডে ক্লাস নেই' : 'No class in this period' }}
    </span>
</div>

@if($activeTeachers->isNotEmpty() && $maxPeriod > 0)
<table class="rwr-print-table">
    <thead>
        <tr>
            <th class="th-teacher">
                {{ $lang === 'bn' ? 'শিক্ষক' : 'Teacher' }}
            </th>
            @for($p = 1; $p <= $maxPeriod; $p++)
                <th>
                    {{ $lang === 'bn' ? 'পিরিওড ' . $toBn($p) : 'Period ' . $p }}
                </th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($activeTeachers as $teacher)
            @php
                $teacherName = $teacher->full_name_bn ?: ($teacher->full_name ?? optional($teacher->user)->name ?? 'N/A');
                $initials    = $teacher->initials ? " [{$teacher->initials}]" : '';
                $designation = $teacher->designation ?? '';
            @endphp
            <tr>
                <td class="td-teacher-print">
                    {{ $teacherName }}{{ $initials }}
                    @if($designation)
                        <small>{{ $designation }}</small>
                    @endif
                </td>
                @for($p = 1; $p <= $maxPeriod; $p++)
                    @php
                        $cellKey     = $teacher->id . '#' . $p;
                        $cellEntries = $routineGrid->get($cellKey, collect());
                    @endphp
                    @if($cellEntries->isNotEmpty())
                        @php
                            $entry       = $cellEntries->first();
                            $subjName    = $lang === 'bn'
                                ? ($entry->subject?->bangla_name ?: $entry->subject?->name ?? '?')
                                : ($entry->subject?->name ?? '?');
                            $clsName     = $lang === 'bn'
                                ? ($entry->class?->bangla_name ?: $entry->class?->name ?? '?')
                                : ($entry->class?->name ?? '?');
                            $secName     = $lang === 'bn'
                                ? ($entry->section?->bangla_name ?: $entry->section?->name ?? '')
                                : ($entry->section?->name ?? '');

                            $evalKey1    = 're#' . $entry->id;
                            $evalKey2    = $teacher->id . '#' . $entry->class_id . '#' . $entry->section_id . '#' . $entry->subject_id;
                            $hasEval     = isset($evalLookup[$evalKey1]) || isset($evalLookup[$evalKey2]);
                        @endphp
                        <td class="td-period-print has-data">
                            <div class="pr-cell-content">
                                <div class="pr-info">
                                    <span class="pr-subject">{{ $subjName }}</span>
                                    <span class="pr-meta">{{ $clsName }}{{ $secName ? ' - '.$secName : '' }}</span>
                                </div>
                                <span class="pr-status">
                                    @if($hasEval)
                                        <span class="pr-tick">✓</span>
                                    @else
                                        <span class="pr-cross">✗</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                    @else
                        <td class="td-period-print no-data">
                            <span class="pr-empty-dash">—</span>
                        </td>
                    @endif
                @endfor
            </tr>
        @endforeach
    </tbody>
</table>
@else
    <p style="text-align:center; color:#666; padding:30px;">
        {{ $lang === 'bn' ? $dayNameBn . '-এ কোনো রুটিন পাওয়া যায়নি।' : 'No routine found for ' . ucfirst($dayOfWeek) . '.' }}
    </p>
@endif
@endsection
