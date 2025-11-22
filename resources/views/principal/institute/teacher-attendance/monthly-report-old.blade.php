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
                            <button class="btn btn-primary btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> প্রিন্ট
                            </button>
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

                        <div class="print-area">
                            <div class="text-center mb-3 d-none d-print-block print-header">
                                <h3>{{ $school->name }}</h3>
                                <p>{{ $school->address }}</p>
                                <hr>
                                <h4>শিক্ষক মাসিক হাজিরা রিপোর্ট</h4>
                                <p>মাস: {{ \Carbon\Carbon::parse($month)->format('F Y') }}</p>
                            </div>

                            @if($teachers->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" style="font-size: 11px;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th rowspan="2" class="align-middle" width="5%">ক্রমিক</th>
                                                <th rowspan="2" class="align-middle" width="15%">শিক্ষকের নাম</th>
                                                <th colspan="{{ count($dates) }}" class="text-center">তারিখ</th>
                                                <th rowspan="2" class="align-middle text-center" width="8%">মোট</th>
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

                                <div class="mt-3">
                                    <h6>চিহ্নসমূহ:</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <span class="badge badge-success">P</span> = উপস্থিত (Present)
                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge badge-warning">L</span> = বিলম্ব (Late)
                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge badge-danger">A</span> = অনুপস্থিত (Absent)
                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge badge-info">H</span> = হাফ ডে (Half Day)
                                        </div>
                                    </div>
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
    </div>
</section>

@endsection

<style>
@media print {
    body {
        margin: 0;
        padding: 0;
    }
    
    .card {
        border: none;
        box-shadow: none;
    }
    
    .card-header, .card-tools, .breadcrumb, .content-header {
        display: none !important;
    }
    
    .print-header {
        margin-bottom: 20px;
    }
    
    .print-header h3 {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .print-header h4 {
        font-size: 16px;
        margin-top: 10px;
    }
    
    .print-header p {
        margin: 3px 0;
        font-size: 12px;
    }
    
    .table {
        font-size: 8px !important;
        width: 100%;
    }
    
    .table th, .table td {
        padding: 3px 2px !important;
        border: 1px solid #000 !important;
    }
    
    .table thead th {
        background-color: #f0f0f0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Color printing for attendance status */
    [style*="background-color: #d4edda"] {
        background-color: #d4edda !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    [style*="background-color: #fff3cd"] {
        background-color: #fff3cd !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    [style*="background-color: #f8d7da"] {
        background-color: #f8d7da !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    [style*="background-color: #e2e3e5"] {
        background-color: #e2e3e5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    @page {
        size: A4 landscape;
        margin: 10mm;
    }
    
    /* Auto-fit table to page */
    .table-responsive {
        overflow: visible !important;
    }
    
    /* Footer */
    @page {
        @bottom-center {
            content: "Page " counter(page) " of " counter(pages);
        }
    }
    
    .print-footer {
        position: fixed;
        bottom: 0;
        width: 100%;
        text-align: center;
        font-size: 10px;
        padding-top: 10px;
        border-top: 1px solid #000;
    }
}

/* Ensure colors show on screen too */
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }
.text-info { color: #17a2b8 !important; }
</style>
