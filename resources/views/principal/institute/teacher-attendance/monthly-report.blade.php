@extends('layouts.admin')

@section('title', 'Teacher Monthly Attendance Report')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">শিক্ষক মাসিক হাজিরা রিপোর্ট</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item active">শিক্ষক মাসিক হাজিরা</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">মাসিক হাজিরা রিপোর্ট</h3>
                        <div class="card-tools">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                    <i class="fas fa-print"></i> প্রিন্ট বিকল্প
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <h6 class="dropdown-header">বাংলা</h6>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.monthly.print', ['school' => $school, 'month' => $month, 'pageSize' => 'a4', 'timeFormat' => '24', 'lang' => 'bn']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file-alt"></i> A4 (24 ঘন্টা)
                                    </a>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.monthly.print', ['school' => $school, 'month' => $month, 'pageSize' => 'a4', 'timeFormat' => '12', 'lang' => 'bn']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file-alt"></i> A4 (AM/PM)
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.monthly.print', ['school' => $school, 'month' => $month, 'pageSize' => 'legal', 'timeFormat' => '24', 'lang' => 'bn']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file"></i> Legal (24 ঘন্টা)
                                    </a>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.monthly.print', ['school' => $school, 'month' => $month, 'pageSize' => 'legal', 'timeFormat' => '12', 'lang' => 'bn']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file"></i> Legal (AM/PM)
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <h6 class="dropdown-header">English</h6>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.monthly.print', ['school' => $school, 'month' => $month, 'pageSize' => 'a4', 'timeFormat' => '24', 'lang' => 'en']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file-alt"></i> A4 (24 Hour)
                                    </a>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.monthly.print', ['school' => $school, 'month' => $month, 'pageSize' => 'a4', 'timeFormat' => '12', 'lang' => 'en']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file-alt"></i> A4 (AM/PM)
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.monthly.print', ['school' => $school, 'month' => $month, 'pageSize' => 'legal', 'timeFormat' => '24', 'lang' => 'en']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file"></i> Legal (24 Hour)
                                    </a>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.monthly.print', ['school' => $school, 'month' => $month, 'pageSize' => 'legal', 'timeFormat' => '12', 'lang' => 'en']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file"></i> Legal (AM/PM)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('principal.institute.teacher-attendance.reports.monthly', $school) }}" class="mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="month">মাস নির্বাচন করুন</label>
                                        <input type="month" 
                                               class="form-control" 
                                               id="month" 
                                               name="month" 
                                               value="{{ $month }}"
                                               max="{{ \Carbon\Carbon::now()->format('Y-m') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> দেখুন
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if($teachers->count() > 0)
                            <div class="mb-3">
                                <strong>চিহ্নসমূহ:</strong>
                                <span class="badge badge-success ml-2">P = উপস্থিত</span>
                                <span class="badge badge-warning ml-2">L = বিলম্ব</span>
                                <span class="badge badge-danger ml-2">A = অনুপস্থিত</span>
                                <span class="badge badge-info ml-2">H = হাফ ডে</span>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm" style="font-size: 11px;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th rowspan="2" class="align-middle" width="5%">ক্রমিক</th>
                                            <th rowspan="2" class="align-middle" width="15%">শিক্ষকের নাম</th>
                                            <th colspan="{{ count($dates) }}" class="text-center">তারিখ</th>
                                            <th rowspan="2" class="align-middle text-center" width="8%">সারসংক্ষেপ</th>
                                        </tr>
                                        <tr>
                                            @foreach($dates as $date)
                                                <th class="text-center" style="min-width: 35px;">
                                                    {{ $date->format('d') }}
                                                    <br>
                                                    <small>{{ $date->format('D') }}</small>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($teachers as $index => $teacher)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $teacher->full_name }}</td>
                                                @php
                                                    $presentCount = 0;
                                                    $lateCount = 0;
                                                    $absentCount = 0;
                                                @endphp
                                                @foreach($dates as $date)
                                                    @php
                                                        $dateStr = $date->format('Y-m-d');
                                                        $attendance = $teacher->teacherAttendances->first(function($att) use ($dateStr) {
                                                            return \Carbon\Carbon::parse($att->date)->format('Y-m-d') === $dateStr;
                                                        });
                                                        if ($attendance) {
                                                            if ($attendance->status === 'present') {
                                                                $presentCount++;
                                                            } elseif ($attendance->status === 'late') {
                                                                $lateCount++;
                                                            }
                                                        } else {
                                                            $absentCount++;
                                                        }
                                                    @endphp
                                                    <td class="text-center"
                                                        @if($attendance)
                                                            @if($attendance->status === 'present')
                                                                style="background-color: #d4edda;"
                                                            @elseif($attendance->status === 'late')
                                                                style="background-color: #fff3cd;"
                                                            @elseif($attendance->status === 'absent')
                                                                style="background-color: #f8d7da;"
                                                            @endif
                                                        @else
                                                            style="background-color: #e2e3e5;"
                                                        @endif
                                                        title="@if($attendance) {{ ucfirst($attendance->status) }} @else Absent @endif">
                                                        @if($attendance)
                                                            @if($attendance->status === 'present')
                                                                <span class="text-success"><strong>P</strong></span>
                                                            @elseif($attendance->status === 'late')
                                                                <span class="text-warning"><strong>L</strong></span>
                                                            @elseif($attendance->status === 'absent')
                                                                <span class="text-danger"><strong>A</strong></span>
                                                            @else
                                                                <span class="text-info"><strong>H</strong></span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="text-center">
                                                    <small>
                                                        <span class="text-success">P:{{ $presentCount }}</span> |
                                                        <span class="text-warning">L:{{ $lateCount }}</span> |
                                                        <span class="text-danger">A:{{ $absentCount }}</span>
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> এই প্রতিষ্ঠানে কোনো শিক্ষক নেই।
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
