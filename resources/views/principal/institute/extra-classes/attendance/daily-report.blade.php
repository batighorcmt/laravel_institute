@extends('layouts.admin')

@section('title', 'Daily Attendance Report - ' . $extraClass->name)

@section('content')
@if(!$print)
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Daily Attendance Report</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $school) }}">Extra Class Attendance</a></li>
                    <li class="breadcrumb-item active">Daily Report</li>
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
                <h3 class="card-title">{{ $extraClass->name }} - {{ \Carbon\Carbon::parse($date)->format('d F, Y') }}</h3>
                <div class="card-tools">
                    <a href="?extra_class_id={{ $extraClass->id }}&date={{ $date }}&print=1" 
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

            <div class="card-body">
                @if($print)
                <div class="text-center mb-4">
                    <h3 class="mb-1">{{ $school->name }}</h3>
                    <h5 class="mb-1">{{ $school->address ?? '' }}</h5>
                    <h4 class="mb-1 mt-3"><u>এক্সট্রা ক্লাস দৈনিক হাজিরা রিপোর্ট</u></h4>
                    <p class="mb-0">
                        <strong>এক্সট্রা ক্লাস:</strong> {{ $extraClass->name }} |
                        <strong>শ্রেণি:</strong> {{ $extraClass->schoolClass->name ?? 'N/A' }} |
                        <strong>সেকশন:</strong> {{ $extraClass->section->name ?? 'N/A' }}
                    </p>
                    <p class="mb-0">
                        <strong>বিষয়:</strong> {{ $extraClass->subject->name ?? 'N/A' }} |
                        <strong>শিক্ষক:</strong> {{ $extraClass->teacher->name ?? 'N/A' }}
                    </p>
                    <p><strong>তারিখ:</strong> {{ \Carbon\Carbon::parse($date)->locale('bn')->isoFormat('DD MMMM, YYYY') }}</p>
                </div>
                @endif

                <!-- Statistics -->
                <div class="row mb-3">
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">মোট শিক্ষার্থী</span>
                                <span class="info-box-number">{{ $stats['total'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-user-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">উপস্থিত</span>
                                <span class="info-box-number">{{ $stats['present'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-user-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">অনুপস্থিত</span>
                                <span class="info-box-number">{{ $stats['absent'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">উপস্থিতির হার</span>
                                <span class="info-box-number">{{ $stats['percentage'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($attendances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th width="50" class="text-center">ক্রমিক</th>
                                    <th width="80" class="text-center">রোল</th>
                                    <th>শিক্ষার্থীর নাম</th>
                                    <th width="120" class="text-center">সেকশন</th>
                                    <th width="120" class="text-center">উপস্থিতি</th>
                                    <th>মন্তব্য</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendances->sortBy(function($att) {
                                    return $att->student->currentEnrollment->roll_no ?? 9999;
                                }) as $index => $attendance)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center">{{ $attendance->student->currentEnrollment->roll_no ?? 'N/A' }}</td>
                                        <td>{{ $attendance->student->student_name_bn ?? $attendance->student->student_name_en }}</td>
                                        <td class="text-center">
                                            {{ $attendance->student->currentEnrollment->section->name ?? 'N/A' }}
                                        </td>
                                        <td class="text-center">
                                            @if($attendance->status === 'present')
                                                <span class="badge badge-success">উপস্থিত</span>
                                            @elseif($attendance->status === 'absent')
                                                <span class="badge badge-danger">অনুপস্থিত</span>
                                            @elseif($attendance->status === 'late')
                                                <span class="badge badge-warning">বিলম্ব</span>
                                            @elseif($attendance->status === 'excused')
                                                <span class="badge badge-info">ছুটি</span>
                                            @endif
                                        </td>
                                        <td>{{ $attendance->remarks ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> এই তারিখের জন্য কোনো হাজিরা রেকর্ড পাওয়া যায়নি।
                    </div>
                @endif

                @if($print)
                <div class="row mt-5">
                    <div class="col-4 text-center">
                        <p>_________________<br>শিক্ষক স্বাক্ষর</p>
                    </div>
                    <div class="col-4 text-center">
                        <p>_________________<br>প্রধান শিক্ষক</p>
                    </div>
                    <div class="col-4 text-center">
                        <p>_________________<br>তারিখ</p>
                    </div>
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
        .info-box { page-break-inside: avoid; }
        @page { margin: 1cm; }
    }
</style>
<script>
    window.onload = function() { window.print(); }
</script>
@endif
@endsection
