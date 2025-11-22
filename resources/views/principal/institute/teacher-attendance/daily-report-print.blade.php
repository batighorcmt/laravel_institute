@extends('layouts.print')

@php
    $lang = request('lang', 'bn');
    $printTitle = $lang === 'bn' ? 'শিক্ষক দৈনিক হাজিরা রিপোর্ট' : 'Teacher Daily Attendance Report';
    $printSubtitle = $lang === 'bn' 
        ? 'তারিখ: ' . \Carbon\Carbon::parse($date)->locale('bn')->translatedFormat('d F Y (l)')
        : 'Date: ' . \Carbon\Carbon::parse($date)->format('d F Y (l)');
    
    // Bengali number conversion helper
    function toBengaliNumber($number) {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace($en, $bn, $number);
    }
@endphp

@push('print_head')
<style>
    @page { size: A4 portrait; margin: 12mm; }
    .filter-form { margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
    .lang-toggle { margin-left: 15px; display: flex; align-items: center; gap: 10px; }
    .lang-toggle label { margin: 0; font-weight: 600; }
    .lang-toggle select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px; }
    .table th, .table td { border: 1px solid #333; padding: 10px 8px; text-align: left; }
    .table thead th { background-color: #e8e8e8; font-weight: 700; text-align: center; }
    .table tbody td { vertical-align: middle; }
    .text-center { text-align: center; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: 600; }
    .badge-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .badge-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .badge-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .badge-secondary { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    .btn-link { color: #007bff; text-decoration: none; font-size: 12px; font-weight: 600; }
    .summary-row { background-color: #f5f5f5; font-weight: 700; }
    
    @media print {
        .filter-form { display: none !important; }
        .table { font-size: 12px; }
        .table th, .table td { padding: 8px 6px; }
    }
</style>
@endpush

@section('content')

<div class="filter-form no-print">
    <form method="GET" action="{{ route('principal.institute.teacher-attendance.reports.daily.print', $school) }}">
        <div style="display: flex; gap: 15px; align-items: end;">
            <div style="flex: 1;">
                <label for="date" style="display: block; margin-bottom: 5px; font-weight: 600;">{{ $lang === 'bn' ? 'তারিখ নির্বাচন করুন' : 'Select Date' }}</label>
                <input type="date" 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                       id="date" 
                       name="date" 
                       value="{{ $date }}"
                       max="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
            </div>
            <div class="lang-toggle">
                <label for="lang">{{ $lang === 'bn' ? 'ভাষা' : 'Language' }}:</label>
                <select id="lang" name="lang" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="bn" {{ $lang === 'bn' ? 'selected' : '' }}>বাংলা</option>
                    <option value="en" {{ $lang === 'en' ? 'selected' : '' }}>English</option>
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

@if($teachers->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th style="width: 8%;">{{ $lang === 'bn' ? 'ক্রমিক' : 'Serial' }}</th>
                <th style="width: 30%;">{{ $lang === 'bn' ? 'শিক্ষকের নাম' : 'Teacher Name' }}</th>
                <th style="width: 15%;">{{ $lang === 'bn' ? 'চেক-ইন' : 'Check-In' }}</th>
                <th style="width: 15%;">{{ $lang === 'bn' ? 'চেক-আউট' : 'Check-Out' }}</th>
                <th style="width: 12%;">{{ $lang === 'bn' ? 'মোট সময়' : 'Total Time' }}</th>
                <th style="width: 20%;">{{ $lang === 'bn' ? 'স্ট্যাটাস' : 'Status' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $index => $teacher)
                @php
                    $attendance = $teacher->teacherAttendances->first();
                @endphp
                @php
                    $teacherName = $lang === 'bn' 
                        ? trim(($teacher->first_name_bn ?? '') . ' ' . ($teacher->last_name_bn ?? '')) ?: $teacher->full_name
                        : $teacher->full_name;
                @endphp
                <tr>
                    <td class="text-center">{{ $lang === 'bn' ? toBengaliNumber($index + 1) : ($index + 1) }}</td>
                    <td>{{ $teacherName }}</td>
                    <td class="text-center">
                        @if($attendance && $attendance->check_in_time)
                            @php
                                $timeStr = \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A');
                            @endphp
                            {{ $lang === 'bn' ? toBengaliNumber($timeStr) : $timeStr }}
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($attendance && $attendance->check_out_time)
                            @php
                                $timeStr = \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A');
                            @endphp
                            {{ $lang === 'bn' ? toBengaliNumber($timeStr) : $timeStr }}
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($attendance && $attendance->check_in_time && $attendance->check_out_time)
                            @php
                                $checkIn = \Carbon\Carbon::parse($attendance->check_in_time);
                                $checkOut = \Carbon\Carbon::parse($attendance->check_out_time);
                                $diff = $checkIn->diff($checkOut);
                                $hourLabel = $lang === 'bn' ? 'ঘ' : 'h';
                                $minLabel = $lang === 'bn' ? 'মি' : 'm';
                                $hourNum = $lang === 'bn' ? toBengaliNumber($diff->h) : $diff->h;
                                $minNum = $lang === 'bn' ? toBengaliNumber($diff->i) : $diff->i;
                            @endphp
                            {{ $hourNum }}{{ $hourLabel }} {{ $minNum }}{{ $minLabel }}
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($attendance)
                            @if($attendance->status === 'present')
                                <span class="badge badge-success">{{ $lang === 'bn' ? 'উপস্থিত' : 'Present' }}</span>
                            @elseif($attendance->status === 'late')
                                <span class="badge badge-warning">{{ $lang === 'bn' ? 'বিলম্ব' : 'Late' }}</span>
                            @elseif($attendance->status === 'absent')
                                <span class="badge badge-danger">{{ $lang === 'bn' ? 'অনুপস্থিত' : 'Absent' }}</span>
                            @else
                                <span class="badge badge-secondary">{{ $lang === 'bn' ? 'হাফ ডে' : 'Half Day' }}</span>
                            @endif
                        @else
                            <span class="badge badge-secondary">{{ $lang === 'bn' ? 'নেই' : 'None' }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="summary-row">
                @php
                    $presentCount = $teachers->filter(fn($t) => $t->teacherAttendances->first()?->status === 'present')->count();
                    $lateCount = $teachers->filter(fn($t) => $t->teacherAttendances->first()?->status === 'late')->count();
                    $absentCount = $teachers->filter(fn($t) => !$t->teacherAttendances->first())->count();
                @endphp
                <td colspan="5" style="text-align: right; padding-right: 15px;">{{ $lang === 'bn' ? 'সারসংক্ষেপ:' : 'Summary:' }}</td>
                <td class="text-center">
                    <strong>{{ $lang === 'bn' ? 'উপস্থিত:' : 'Present:' }}</strong> {{ $lang === 'bn' ? toBengaliNumber($presentCount) : $presentCount }} |
                    <strong>{{ $lang === 'bn' ? 'বিলম্ব:' : 'Late:' }}</strong> {{ $lang === 'bn' ? toBengaliNumber($lateCount) : $lateCount }} |
                    <strong>{{ $lang === 'bn' ? 'অনুপস্থিত:' : 'Absent:' }}</strong> {{ $lang === 'bn' ? toBengaliNumber($absentCount) : $absentCount }}
                </td>
            </tr>
        </tfoot>
    </table>
@else
    <p style="text-align: center; padding: 20px; color: #666;">{{ $lang === 'bn' ? 'এই প্রতিষ্ঠানে কোনো শিক্ষক নেই।' : 'No teachers found in this institution.' }}</p>
@endif

@endsection
