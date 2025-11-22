@extends('layouts.print')

@php
    $lang = request('lang', 'bn');
    $printTitle = $lang === 'bn' ? 'শিক্ষক মাসিক হাজিরা রিপোর্ট' : 'Teacher Monthly Attendance Report';
    $monthName = $lang === 'bn' 
        ? \Carbon\Carbon::parse($month . '-01')->locale('bn')->translatedFormat('F Y')
        : \Carbon\Carbon::parse($month . '-01')->format('F Y');
    $printSubtitle = ($lang === 'bn' ? 'মাস: ' : 'Month: ') . $monthName;
    $pageSize = request('pageSize', 'a4'); // a4 or legal
    $timeFormat = request('timeFormat', '24'); // 24 or 12
    
    // Bengali number conversion helper
    function toBengaliNumber($number) {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace($en, $bn, $number);
    }
@endphp

@push('print_head')
<style>
    /* Dynamic page size based on selection */
    @page { 
        @if($pageSize === 'legal')
            size: legal landscape;
        @else
            size: A4 landscape;
        @endif
        margin: {{ $pageSize === 'legal' ? '6mm' : '8mm' }}; 
    }
    
    .filter-form { margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
    .lang-toggle { margin-left: 15px; display: flex; align-items: center; gap: 10px; }
    .lang-toggle label { margin: 0; font-weight: 600; }
    .lang-toggle select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .page-size-selector { margin-left: 15px; display: flex; align-items: center; gap: 10px; }
    .page-size-selector label { margin: 0; font-weight: 600; }
    .page-size-selector select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    
    /* Time format selector */
    .time-format-selector { margin-left: 15px; display: flex; align-items: center; gap: 10px; }
    .time-format-selector label { margin: 0; font-weight: 600; }
    .time-format-selector select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    
    .attendance-table { 
        width: 100%; 
        border-collapse: collapse; 
        font-size: {{ $pageSize === 'legal' ? '11px' : '9px' }}; 
        margin-top: 10px;
        table-layout: fixed;
    }
    .attendance-table th, .attendance-table td { 
        border: 1px solid #333; 
        padding: {{ $pageSize === 'legal' ? '5px 3px' : '4px 2px' }}; 
        text-align: center; 
        vertical-align: middle;
        overflow: hidden;
    }
    .attendance-table thead th { 
        background-color: #e8e8e8; 
        font-weight: 700; 
        font-size: {{ $pageSize === 'legal' ? '11px' : '9px' }}; 
        line-height: 1.2;
    }
    .attendance-table tbody td:first-child { text-align: center; font-weight: 600; }
    .attendance-table tbody td:nth-child(2) { 
        text-align: left; 
        font-weight: 600; 
        padding-left: 5px; 
    }
    
    .serial-cell { width: {{ $pageSize === 'legal' ? '3%' : '2.5%' }}; }
    .name-cell { 
        width: {{ $pageSize === 'legal' ? '13%' : '10%' }}; 
        text-align: left !important; 
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: {{ $pageSize === 'legal' ? '140px' : '100px' }};
    }
    .date-cell { 
        font-size: {{ $pageSize === 'legal' ? '9px' : '7.5px' }}; 
        line-height: 1.2;
        padding: {{ $pageSize === 'legal' ? '4px 2px' : '3px 1px' }} !important;
        width: {{ $pageSize === 'legal' ? '2.6%' : '2.3%' }};
    }
    .summary-cell { 
        width: {{ $pageSize === 'legal' ? '7%' : '6%' }}; 
        font-size: {{ $pageSize === 'legal' ? '10px' : '8px' }}; 
    }
    
    /* More vibrant background colors */
    .att-present { 
        background-color: #c3f0c3 !important; 
        color: #0a5f0a; 
        -webkit-print-color-adjust: exact; 
        print-color-adjust: exact; 
        font-weight: 600; 
    }
    .att-late { 
        background-color: #fff4b3 !important; 
        color: #b8860b; 
        -webkit-print-color-adjust: exact; 
        print-color-adjust: exact; 
        font-weight: 600; 
    }
    .att-absent { 
        background-color: #ffcccb !important; 
        color: #8b0000; 
        -webkit-print-color-adjust: exact; 
        print-color-adjust: exact; 
        font-weight: 600; 
    }
    .att-half { 
        background-color: #d4d4f7 !important; 
        color: #2e2e8b; 
        -webkit-print-color-adjust: exact; 
        print-color-adjust: exact; 
        font-weight: 600; 
    }
    .att-holiday { 
        background-color: #ffe4b5 !important; 
        color: #cd853f; 
        -webkit-print-color-adjust: exact; 
        print-color-adjust: exact; 
        font-style: italic; 
    }
    .att-none { 
        background-color: #fafafa !important; 
        color: #ccc; 
    }
    
    .time-display { 
        font-size: {{ $pageSize === 'legal' ? '8px' : '7px' }}; 
        line-height: 1.3; 
        font-weight: 700;
    }
    .time-in { color: #0a5f0a; }
    .time-out { color: #b8860b; }
    
    .legend { 
        display: flex; 
        justify-content: center; 
        gap: 12px; 
        margin: 8px 0; 
        font-size: 11px; 
        flex-wrap: wrap; 
    }
    .legend-item { 
        display: flex; 
        align-items: center; 
        gap: 4px; 
    }
    .legend-box { 
        width: 16px; 
        height: 16px; 
        border: 1px solid #333; 
        display: inline-block; 
    }
    
    @media print {
        .filter-form, .lang-toggle, .page-size-selector, .time-format-selector { 
            display: none !important; 
        }
        
        @page { 
            @if($pageSize === 'legal')
                size: legal landscape;
            @else
                size: A4 landscape;
            @endif
            margin: {{ $pageSize === 'legal' ? '6mm' : '8mm' }}; 
        }
        
        .attendance-table {
            page-break-inside: auto;
        }
        
        .attendance-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    }
</style>
@endpush

@section('content')

<div class="filter-form no-print">
    <form method="GET" action="{{ route('principal.institute.teacher-attendance.reports.monthly.print', $school) }}">
        <div style="display: flex; gap: 15px; align-items: end;">
            <div style="flex: 1;">
                <label for="month" style="display: block; margin-bottom: 5px; font-weight: 600;">{{ $lang === 'bn' ? 'মাস নির্বাচন করুন' : 'Select Month' }}</label>
                <input type="month" 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                       id="month" 
                       name="month" 
                       value="{{ $month }}"
                       max="{{ \Carbon\Carbon::now()->format('Y-m') }}">
            </div>
            <div class="lang-toggle">
                <label for="lang">{{ $lang === 'bn' ? 'ভাষা' : 'Language' }}:</label>
                <select id="lang" name="lang" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="bn" {{ $lang === 'bn' ? 'selected' : '' }}>বাংলা</option>
                    <option value="en" {{ $lang === 'en' ? 'selected' : '' }}>English</option>
                </select>
            </div>
            <div class="page-size-selector">
                <label for="pageSize">{{ $lang === 'bn' ? 'পৃষ্ঠার সাইজ:' : 'Page Size:' }}</label>
                <select id="pageSize" name="pageSize" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="a4" {{ $pageSize === 'a4' ? 'selected' : '' }}>A4</option>
                    <option value="legal" {{ $pageSize === 'legal' ? 'selected' : '' }}>Legal</option>
                </select>
            </div>
            <div class="time-format-selector">
                <label for="timeFormat">{{ $lang === 'bn' ? 'সময় ফরম্যাট:' : 'Time Format:' }}</label>
                <select id="timeFormat" name="timeFormat" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="24" {{ $timeFormat === '24' ? 'selected' : '' }}>{{ $lang === 'bn' ? '24 ঘন্টা' : '24 Hour' }}</option>
                    <option value="12" {{ $timeFormat === '12' ? 'selected' : '' }}>AM/PM</option>
                </select>
            </div>
            <div>
                <button type="submit" style="padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    {{ $lang === 'bn' ? 'দেখুন' : 'View' }}
                </button>
            </div>
        </div>
    </form>
</div>

<div class="legend">
    <div class="legend-item">
        <span class="legend-box att-present"></span>
        <span><strong>{{ $lang === 'bn' ? 'উপস্থিত' : 'Present' }}</strong> ({{ $lang === 'bn' ? 'সময় সহ' : 'with time' }})</span>
    </div>
    <div class="legend-item">
        <span class="legend-box att-late"></span>
        <span><strong>{{ $lang === 'bn' ? 'বিলম্ব' : 'Late' }}</strong> ({{ $lang === 'bn' ? 'সময় সহ' : 'with time' }})</span>
    </div>
    <div class="legend-item">
        <span class="legend-box att-absent"></span>
        <span><strong>{{ $lang === 'bn' ? 'অনুপস্থিত' : 'Absent' }}</strong></span>
    </div>
    <div class="legend-item">
        <span class="legend-box att-half"></span>
        <span><strong>{{ $lang === 'bn' ? 'হাফ ডে' : 'Half Day' }}</strong></span>
    </div>
    <div class="legend-item">
        <span class="legend-box att-holiday"></span>
        <span><strong>{{ $lang === 'bn' ? 'ছুটির দিন' : 'Holiday' }}</strong> ({{ $lang === 'bn' ? 'সাপ্তাহিক/বিশেষ' : 'weekly/special' }})</span>
    </div>
</div>

@if($teachers->count() > 0)
    <table class="attendance-table">
        <thead>
            <tr>
                <th class="serial-cell" rowspan="2">{{ $lang === 'bn' ? 'ক্রমিক' : 'Serial' }}</th>
                <th class="name-cell" rowspan="2">{{ $lang === 'bn' ? 'শিক্ষকের নাম' : 'Teacher Name' }}</th>
                @foreach($dates as $date)
                    @php
                        $dayNum = \Carbon\Carbon::parse($date)->format('d');
                        $dayName = $lang === 'bn' 
                            ? \Carbon\Carbon::parse($date)->locale('bn')->translatedFormat('D')
                            : \Carbon\Carbon::parse($date)->format('D');
                    @endphp
                    <th class="date-cell" colspan="1">
                        {{ $lang === 'bn' ? toBengaliNumber($dayNum) : $dayNum }}
                        <br>
                        <small style="font-size: 6px;">{{ $dayName }}</small>
                    </th>
                @endforeach
                <th class="summary-cell" rowspan="2">{{ $lang === 'bn' ? 'সারসংক্ষেপ' : 'Summary' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $index => $teacher)
                @php
                    $presentCount = 0;
                    $lateCount = 0;
                    $absentCount = 0;
                    $teacherName = $lang === 'bn' 
                        ? trim(($teacher->first_name_bn ?? '') . ' ' . ($teacher->last_name_bn ?? '')) ?: $teacher->full_name
                        : $teacher->full_name;
                @endphp
                <tr>
                    <td class="serial-cell">{{ $lang === 'bn' ? toBengaliNumber($index + 1) : ($index + 1) }}</td>
                    <td class="name-cell" title="{{ $teacherName }}">{{ $teacherName }}</td>
                    @foreach($dates as $date)
                        @php
                            $dateStr = \Carbon\Carbon::parse($date)->format('Y-m-d');
                            $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
                            
                            $attendance = $teacher->teacherAttendances->first(function($att) use ($dateStr) {
                                return \Carbon\Carbon::parse($att->date)->format('Y-m-d') === $dateStr;
                            });
                            
                            // Check if it's a weekly holiday (from weeklyHolidays table)
                            $isWeeklyHoliday = in_array($dayOfWeek, $weeklyHolidays ?? []);
                            
                            // Check if it's a special holiday (from holidays table)
                            $isSpecialHoliday = in_array($dateStr, $holidays ?? []);
                            
                            $isHoliday = $isWeeklyHoliday || $isSpecialHoliday;
                            
                            if ($attendance) {
                                if ($attendance->status === 'present') {
                                    $cellClass = 'att-present';
                                    $presentCount++;
                                } elseif ($attendance->status === 'late') {
                                    $cellClass = 'att-late';
                                    $lateCount++;
                                } elseif ($attendance->status === 'absent') {
                                    $cellClass = 'att-absent';
                                    $absentCount++;
                                } else {
                                    $cellClass = 'att-half';
                                }
                            } else {
                                if ($isHoliday) {
                                    $cellClass = 'att-holiday';
                                } else {
                                    $cellClass = 'att-none';
                                    $absentCount++;
                                }
                            }
                        @endphp
                        <td class="date-cell {{ $cellClass }}">
                            @if($attendance)
                                <div class="time-display">
                                    @if($attendance->check_in_time)
                                        @php
                                            $inTime = $timeFormat === '12' 
                                                ? \Carbon\Carbon::parse($attendance->check_in_time)->format('g:i A') 
                                                : \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i');
                                        @endphp
                                        <div class="time-in">
                                            {{ $lang === 'bn' ? toBengaliNumber($inTime) : $inTime }}
                                        </div>
                                    @endif
                                    @if($attendance->check_out_time)
                                        @php
                                            $outTime = $timeFormat === '12' 
                                                ? \Carbon\Carbon::parse($attendance->check_out_time)->format('g:i A') 
                                                : \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i');
                                        @endphp
                                        <div class="time-out">
                                            {{ $lang === 'bn' ? toBengaliNumber($outTime) : $outTime }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                @if($isHoliday)
                                    <span style="font-size: {{ $pageSize === 'legal' ? '8px' : '7px' }}; font-weight: 600;">{{ $lang === 'bn' ? 'ছুটি' : 'Holiday' }}</span>
                                @else
                                    <span style="color: #999;">-</span>
                                @endif
                            @endif
                        </td>
                    @endforeach
                    <td class="summary-cell">
                        <small>
                            <span style="color: #155724;">{{ $lang === 'bn' ? 'উ:' : 'P:' }}{{ $lang === 'bn' ? toBengaliNumber($presentCount) : $presentCount }}</span> | 
                            <span style="color: #856404;">{{ $lang === 'bn' ? 'বি:' : 'L:' }}{{ $lang === 'bn' ? toBengaliNumber($lateCount) : $lateCount }}</span> | 
                            <span style="color: #721c24;">{{ $lang === 'bn' ? 'অ:' : 'A:' }}{{ $lang === 'bn' ? toBengaliNumber($absentCount) : $absentCount }}</span>
                        </small>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p style="text-align: center; padding: 20px; color: #666;">{{ $lang === 'bn' ? 'এই প্রতিষ্ঠানে কোনো শিক্ষক নেই।' : 'No teachers found in this institution.' }}</p>
@endif

@endsection
