@extends('layouts.admin')

@section('title','মাসিক হাজিরা রিপোর্ট')

@push('styles')
<style>
    .attendance-table { font-size: 0.9rem; }
    .attendance-table th { background-color: #f8f9fc; color: #4e73df; font-weight: 600; text-align: center; vertical-align: middle; padding: 6px 6px; }
    .attendance-table td { text-align: center; vertical-align: middle; padding: 6px 6px; }
    .present-icon { color: #28a745; }
    .absent-icon { color: #dc3545; }
    .late-icon { color: #ffc107; }
    .half-day-icon { color: #17a2b8; }
    .holiday-label { font-size: 0.65rem; font-weight: 600; color:#6c757d; }
    /* Show only in print */
    .print-only { display: none; }
    @media print {
        @page { size: landscape; margin: 0.5cm; }
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        /* Hide global layout chrome in print */
        .main-footer, .main-header, .main-sidebar, nav.main-header, aside.main-sidebar { display: none !important; }
        .content-wrapper { margin-left: 0 !important; }
        .container-fluid { padding: 0 !important; margin-top:110px !important; }
        .card { margin: 0 !important; border: 0 !important; box-shadow: none !important; }
        .card-body { padding: 0 !important; }
        .table-responsive { overflow: visible !important; }
        /* Fit table to page width */
        .attendance-table { width: 100% !important; table-layout: fixed; font-size: 0.65rem; }
        .attendance-table th, .attendance-table td { padding: 2px 2px !important; }
        /* Narrow left columns in print */
    .attendance-table th:first-child, .attendance-table td:first-child { min-width: 42px !important; width: 42px !important; }
    .attendance-table th:nth-child(2), .attendance-table td:nth-child(2) { min-width: 140px !important; width: 140px !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        /* Keep cells compact */
        .attendance-table th, .attendance-table td { white-space: nowrap; overflow: hidden; text-overflow: clip; }
        /* Reserve space for fixed footer */
        body { margin-bottom: 70px !important; }
        .print-footer { position: fixed; bottom: 0; left: 0; right: 0; }
        .print-header { position: fixed; top: 0; left: 0; right: 0; z-index:999; background:#fff; }
        /* Increased margin-top to clear new taller header */
    }
</style>
@endpush

@section('content')
@php
    // Initialize collections early (before any HTML) so nothing leaks as raw text
    $studentsCollection = isset($studentsCollection) ? $studentsCollection : collect($students ?? []);
    $dateList = collect($dates ?? [])->values()->all();
    $holidayList = collect($holidayDates ?? [])->values()->all();
    $weeklyHolidayNumsList = collect($weeklyHolidayNums ?? [])->values()->all();
    $dateCount = count($dateList);
    // Bengali localization helpers (digits, months, weekdays)
    $bnDigits = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
    $toBn = function($value) use ($bnDigits) {
        $str = (string)$value;
        return strtr($str, $bnDigits);
    };
    $bnMonths = [
        1=>'জানুয়ারি',2=>'ফেব্রুয়ারি',3=>'মার্চ',4=>'এপ্রিল',5=>'মে',6=>'জুন',
        7=>'জুলাই',8=>'আগস্ট',9=>'সেপ্টেম্বর',10=>'অক্টোবর',11=>'নভেম্বর',12=>'ডিসেম্বর'
    ];
    $bnWeekdaysShort = [1=>'সোম',2=>'মঙ্গল',3=>'বুধ',4=>'বৃহ.',5=>'শুক্র',6=>'শনি',7=>'রবি'];
    $formatBnDateDMY = function($ymd) use ($toBn) {
        if(empty($ymd)) return '';
        $ts = strtotime($ymd);
        return $toBn(date('d-m-Y', $ts));
    };
@endphp
<div class="print-only">
    @php
        $className = optional(collect($classes)->firstWhere('id', request('class_id')))->name ?? null;
        $sectionName = optional(collect($sections)->firstWhere('id', request('section_id')))->name ?? null;
        $year = null; $monthName = null;
        if(!empty($month)) { $ts = strtotime($month.'-01'); $year = $toBn(date('Y', $ts)); $monthName = $bnMonths[(int)date('n', $ts)] ?? date('F', $ts); }
    @endphp
    @include('partials.print.header', [
        'reportTitle' => 'মাসিক হাজিরা রিপোর্ট',
        'reportType' => 'Attendance',
        'year' => $year,
        'monthName' => $monthName,
        'className' => $className,
        'sectionName' => $sectionName,
    ])
    {{-- Header should appear only when printing --}}
</div>
<div class="container-fluid">
    <div class="d-flex justify-content-end align-items-center mb-3 no-print">
        <button type="button" class="btn btn-success" onclick="window.print()"><i class="fas fa-print"></i> প্রিন্ট</button>
    </div>

    <form method="get" class="row g-2 align-items-end mb-3 no-print">
        <div class="col-md-3">
            <label class="form-label">মাস নির্বাচন করুন</label>
            <input type="month" name="month" class="form-control" value="{{ $month }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">ক্লাস</label>
                <select name="class_id" id="class_id" class="form-control" required onchange="handleClassChange(this)">
                    <option value="" disabled {{ request('class_id') ? '' : 'selected' }}>শ্রেণি নির্বাচন করুন</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">শাখা</label>
                <select name="section_id" id="section_id" class="form-control" {{ request('class_id') ? '' : 'disabled' }} required>
                    <option value="" disabled {{ request('section_id') ? '' : 'selected' }}>{{ request('class_id') ? 'শাখা নির্বাচন করুন' : 'আগে শ্রেণি নির্বাচন করুন' }}</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">দেখান</button>
        </div>
    </form>

    <div class="card">
        @if(empty($requiresSelection) || !$requiresSelection)
        <div class="card-body p-0">
            <div class="table-responsive">
                @php
                    $dateList = collect($dates ?? [])->values()->all();
                    $holidayList = collect($holidayDates ?? [])->values()->all();
                    $weeklyHolidayNumsList = collect($weeklyHolidayNums ?? [])->values()->all();
                    // Re-initialize defensively in table scope (harmless if already set)
                    if(!isset($studentsCollection)) { $studentsCollection = collect($students ?? []); }
                    $dateCount = count($dateList);
                @endphp
                <table class="table table-bordered table-striped attendance-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width:70px;">রোল</th>
                            <th rowspan="2" style="min-width:200px;">শিক্ষার্থীর নাম</th>
                            @foreach(($dateList ?? []) as $d)
                                @php $dn = date('d', strtotime($d)); $wdn = (int)date('N', strtotime($d)); @endphp
                                <th title="{{ $formatBnDateDMY($d) }}" class="{{ in_array($d, $holidayList) ? 'holiday' : (in_array($wdn, $weeklyHolidayNumsList) ? 'day-off' : '') }}">{{ $toBn($dn) }}</th>
                            @endforeach
                            <th rowspan="2" class="bg-light">মোট উপ.</th>
                            <th rowspan="2" class="bg-light">মোট অনু.</th>
                            <th rowspan="2" class="bg-light">%</th>
                        </tr>
                        <tr>
                            @foreach(($dateList ?? []) as $d)
                                @php $wdn = (int)date('N', strtotime($d)); @endphp
                                <th style="font-size:0.65rem;" class="text-muted">{{ $bnWeekdaysShort[$wdn] ?? '' }}</th>
                            @endforeach

                        </tr>
                    </thead>
                    <tbody>
                        @if($studentsCollection->isNotEmpty())
                            @foreach($studentsCollection as $st)
                                @php $presentCount = 0; $absentCount = 0; $totalCount = 0; @endphp
                                <tr>
                                    <td>{{ $st->roll_no }}</td>
                                    <td>{{ $st->student_name_bn ?? $st->student_name_en }}</td>
                                    @foreach(($dateList ?? []) as $d)
                                        @php
                                            $status = $attendanceMatrix[$st->student_id][$d] ?? null;
                                            $wdnLoop = (int)date('N', strtotime($d));
                                            $isHolidayCell = in_array($d, $holidayList) || in_array($wdnLoop, $weeklyHolidayNumsList);
                                            // Only count attendance if not a holiday and status exists
                                            if(!$isHolidayCell && $status){ $totalCount++; }
                                            if(!$isHolidayCell){
                                                if($status === 'present' || $status === 'late' || $status === 'half_day'){ $presentCount++; }
                                                elseif($status === 'absent'){ $absentCount++; }
                                            }
                                        @endphp
                                        <td>
                                            @if($isHolidayCell)
                                                <span class="holiday-label" title="ছুটি">ছুটি</span>
                                            @elseif($status === 'present')<span class="present-icon" title="উপস্থিত"><i class="fas fa-check"></i></span>
                                            @elseif($status === 'absent')<span class="absent-icon" title="অনুপস্থিত"><i class="fas fa-times"></i></span>
                                            @elseif($status === 'late')<span class="late-icon" title="দেরি"><i class="fas fa-clock"></i></span>
                                            @elseif($status === 'half_day')<span class="half-day-icon" title="অর্ধদিবস"><i class="fas fa-adjust"></i></span>
                                            @else <span>&nbsp;</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="font-weight-bold">{{ $toBn($presentCount) }}</td>
                                    <td class="font-weight-bold">{{ $toBn($absentCount) }}</td>
                                    <td class="font-weight-bold">
                                        @if($totalCount>0)
                                            {{ $toBn(number_format(($presentCount/$totalCount)*100,2)) }}%
                                        @else — @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ 5 + $dateCount }}" class="text-center">কোনো শিক্ষার্থী পাওয়া যায়নি।</td>
                            </tr>
                        @endif
                        @php
                            // Daily totals
                            $dailyPresent = [];
                            $dailyAbsent = [];
                            foreach(($dateList ?? []) as $d){ $dailyPresent[$d]=0; $dailyAbsent[$d]=0; }
                            if(!isset($studentsCollection)) { $studentsCollection = collect($students ?? []); }
                            foreach($studentsCollection as $stRow){
                                foreach(($dateList ?? []) as $d){
                                    $status = $attendanceMatrix[$stRow->student_id][$d] ?? null;
                                    $wdnTmp = (int)date('N', strtotime($d));
                                    $isHoliday = in_array($d, $holidayList) || in_array($wdnTmp, $weeklyHolidayNumsList);
                                    if($isHoliday) { continue; }
                                    if($status === 'present' || $status === 'late' || $status === 'half_day'){ $dailyPresent[$d]++; }
                                    elseif($status === 'absent'){ $dailyAbsent[$d]++; }
                                }
                            }
                            // Sum only non-holiday columns
                            $sumPresent = 0; $sumAbsent = 0;
                            foreach(($dateList ?? []) as $d){
                                $wdnTmp = (int)date('N', strtotime($d));
                                $isHoliday = in_array($d, $holidayList) || in_array($wdnTmp, $weeklyHolidayNumsList);
                                if($isHoliday) { continue; }
                                $sumPresent += $dailyPresent[$d];
                                $sumAbsent += $dailyAbsent[$d];
                            }
                        @endphp
                        @if($studentsCollection->isNotEmpty())
                        <tr class="bg-light">
                            <td colspan="2" class="text-right font-weight-bold">দৈনিক মোট উপস্থিতি</td>
                            @foreach(($dateList ?? []) as $d)
                                @php $wdnLoop = (int)date('N', strtotime($d)); $isHolidayCell = in_array($d,$holidayList) || in_array($wdnLoop,$weeklyHolidayNumsList); @endphp
                                @if($isHolidayCell)
                                    <td class="font-weight-bold text-muted">ছুটি</td>
                                @else
                                    <td class="font-weight-bold text-success">{{ $toBn($dailyPresent[$d]) }}</td>
                                @endif
                            @endforeach
                            <td class="font-weight-bold text-success">{{ $toBn($sumPresent) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr class="bg-light">
                            <td colspan="2" class="text-right font-weight-bold">দৈনিক মোট অনুপস্থিতি</td>
                            @foreach(($dateList ?? []) as $d)
                                @php $wdnLoop = (int)date('N', strtotime($d)); $isHolidayCell = in_array($d,$holidayList) || in_array($wdnLoop,$weeklyHolidayNumsList); @endphp
                                @if($isHolidayCell)
                                    <td class="font-weight-bold text-muted">ছুটি</td>
                                @else
                                    <td class="font-weight-bold text-danger">{{ $toBn($dailyAbsent[$d]) }}</td>
                                @endif
                            @endforeach
                            <td class="font-weight-bold text-danger">{{ $toBn($sumAbsent) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleClassChange(sel){
        try{
            var form = sel.form;
            // reset section selection; temporarily remove required to bypass HTML5 validation
            var sec = form.querySelector('#section_id');
            if(sec){ sec.value = ''; sec.required = false; }
            form.submit();
        }catch(e){
            // no-op
        }
    }
</script>
@endpush
