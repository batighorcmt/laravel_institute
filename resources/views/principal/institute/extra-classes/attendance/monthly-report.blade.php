@extends('layouts.admin')

@section('title', 'Monthly Attendance Report - ' . $extraClass->name)

@section('content')
@if(!$print)
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Monthly Attendance Report</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $school) }}">Extra Class Attendance</a></li>
                    <li class="breadcrumb-item active">Monthly Report</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endif

<section class="content">
    <div class="container-fluid">
        <div class="card">
            @if(!$print)
            <div class="card-header">
                <h3 class="card-title">{{ $extraClass->name }} - {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</h3>
                <div class="card-tools">
                    <a href="?extra_class_id={{ $extraClass->id }}&month={{ $month }}&print=1" 
                       class="btn btn-sm btn-info" target="_blank">
                        <i class="fas fa-print"></i> প্রিন্ট
                    </a>
                    <a href="{{ route('principal.institute.extra-classes.attendance.index', $school) }}" 
                       class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> ফিরে যান
                    </a>
                </div>
            </div>
            @endif

            <div class="card-body" style="{{ $print ? 'font-size: 11px;' : '' }}">
                @if($print)
                <div class="text-center mb-3">
                    <h4 class="mb-1">{{ $school->name }}</h4>
                    <p class="mb-1">{{ $school->address ?? '' }}</p>
                    <h5 class="mb-1 mt-2"><u>এক্সট্রা ক্লাস মাসিক হাজিরা রিপোর্ট</u></h5>
                    <p class="mb-0">
                        <strong>এক্সট্রা ক্লাস:</strong> {{ $extraClass->name }} | 
                        <strong>শ্রেণি:</strong> {{ $extraClass->schoolClass->name ?? 'N/A' }} |
                        <strong>সেকশন:</strong> {{ $extraClass->section->name ?? 'N/A' }}
                    </p>
                    <p class="mb-0">
                        <strong>বিষয়:</strong> {{ $extraClass->subject->name ?? 'N/A' }} |
                        <strong>মাস:</strong> {{ \Carbon\Carbon::parse($month . '-01')->locale('bn')->isoFormat('MMMM YYYY') }}
                    </p>
                </div>
                @endif

                @if($students->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" style="font-size: {{ $print ? '10px' : '12px' }};">
                            <thead class="bg-light">
                                <tr>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 40px;">ক্রমিক</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 50px;">রোল</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 150px;">শিক্ষার্থীর নাম</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 60px;">সেকশন</th>
                                    <th colspan="{{ count($dates) }}" class="text-center">তারিখ</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 50px;">উপস্থিত</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 50px;">অনুপস্থিত</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 50px;">%</th>
                                </tr>
                                <tr>
                                    @foreach($dates as $date)
                                        @php
                                            $dayNum = (int) date('d', strtotime($date));
                                        @endphp
                                        <th class="text-center" style="min-width: 25px; padding: 2px;">{{ $dayNum }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    @php
                                        $presentCount = 0;
                                        $absentCount = 0;
                                        $totalDays = 0;
                                        
                                        foreach($dates as $date) {
                                            $status = $attendanceMatrix[$student->id][$date] ?? null;
                                            if ($status) {
                                                $totalDays++;
                                                if (in_array($status, ['present', 'late'])) {
                                                    $presentCount++;
                                                } elseif ($status === 'absent') {
                                                    $absentCount++;
                                                }
                                            }
                                        }
                                        
                                        $percentage = $totalDays > 0 ? round(($presentCount / $totalDays) * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center"><strong>{{ $student->roll_no }}</strong></td>
                                        <td>{{ $student->name }}</td>
                                        <td class="text-center">{{ $student->section_name }}</td>
                                        
                                        @foreach($dates as $date)
                                            @php
                                                $status = $attendanceMatrix[$student->id][$date] ?? null;
                                                $dayOfWeek = date('w', strtotime($date));
                                                $isWeekend = in_array($dayOfWeek, [5, 6]); // Friday & Saturday
                                            @endphp
                                            <td class="text-center p-1" 
                                                style="background-color: {{ $isWeekend ? '#f0f0f0' : 'white' }};">
                                                @if($status === 'present')
                                                    <span class="text-success font-weight-bold">P</span>
                                                @elseif($status === 'absent')
                                                    <span class="text-danger font-weight-bold">A</span>
                                                @elseif($status === 'late')
                                                    <span class="text-warning font-weight-bold">L</span>
                                                @elseif($status === 'excused')
                                                    <span class="text-info font-weight-bold">E</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        
                                        <td class="text-center font-weight-bold text-success">{{ $presentCount }}</td>
                                        <td class="text-center font-weight-bold text-danger">{{ $absentCount }}</td>
                                        <td class="text-center font-weight-bold">{{ $percentage }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <p class="mb-1"><strong>চিহ্ন ব্যাখ্যা:</strong></p>
                        <ul class="list-unstyled mb-0" style="font-size: {{ $print ? '10px' : '13px' }};">
                            <li><span class="text-success font-weight-bold">P</span> = উপস্থিত (Present)</li>
                            <li><span class="text-danger font-weight-bold">A</span> = অনুপস্থিত (Absent)</li>
                            <li><span class="text-warning font-weight-bold">L</span> = বিলম্ব (Late)</li>
                            <li><span class="text-info font-weight-bold">E</span> = ছুটি (Excused)</li>
                            <li><span class="text-muted">-</span> = হাজিরা নেওয়া হয়নি</li>
                        </ul>
                    </div>

                    @if($print)
                    <div class="row mt-4">
                        <div class="col-4 text-center">
                            <p style="font-size: 11px;">_________________<br>শিক্ষক স্বাক্ষর</p>
                        </div>
                        <div class="col-4 text-center">
                            <p style="font-size: 11px;">_________________<br>প্রধান শিক্ষক</p>
                        </div>
                        <div class="col-4 text-center">
                            <p style="font-size: 11px;">_________________<br>তারিখ</p>
                        </div>
                    </div>
                    @endif
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> এই এক্সট্রা ক্লাসে কোনো শিক্ষার্থী নথিভুক্ত নেই।
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@if($print)
<style>
    @media print {
        body * { visibility: hidden; }
        .content, .content * { visibility: visible; }
        .content { position: absolute; left: 0; top: 0; width: 100%; }
        .card { border: none !important; box-shadow: none !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        @page { 
            margin: 0.5cm; 
            size: landscape;
        }
    }
</style>
<script>
    window.onload = function() { window.print(); }
</script>
@endif
@endsection
