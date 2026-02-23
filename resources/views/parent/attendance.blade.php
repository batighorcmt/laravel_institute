@extends('layouts.admin')
@section('title', $type == 'class' ? 'ক্লাস হাজিরা রিপোর্ট' : 'এক্সট্রা ক্লাস হাজিরা রিপোর্ট')

@push('styles')
<style>
    .info-box-stat {
        border-radius: 15px !important;
        color: white !important;
        padding: 20px !important;
        text-align: center !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        transition: transform 0.3s !important;
        min-height: 100px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        margin-bottom: 20px !important;
    }
    .info-box-stat:hover { transform: translateY(-5px) !important; }
    .bg-gradient-pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important; }
    .bg-gradient-green { background: linear-gradient(135deg, #5ee7ac 0%, #17ead9 100%) !important; }
    .bg-gradient-orange { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%) !important; }
    .bg-gradient-blue { background: linear-gradient(135deg, #2af5ff 0%, #21d4fd 100%) !important; }
    
    .stat-number { font-size: 2.2rem !important; font-weight: bold !important; margin-bottom: 5px !important; line-height: 1 !important; }
    .stat-label { font-size: 1rem !important; opacity: 0.9 !important; font-weight: 500 !important; }

    .attendance-card {
        border-radius: 15px !important;
        border: none !important;
        box-shadow: 0 0 20px rgba(0,0,0,0.05) !important;
    }
    
    .calendar-grid {
        display: grid !important;
        grid-template-columns: repeat(7, 1fr) !important;
        gap: 10px !important;
        margin-top: 20px !important;
    }
    
    .calendar-header {
        text-align: center !important;
        font-weight: bold !important;
        color: #777 !important;
        padding: 10px 0 !important;
    }
    
    .calendar-day {
        aspect-ratio: 1/1 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 10px !important;
        font-weight: 600 !important;
        position: relative !important;
        cursor: pointer !important;
        min-height: 40px !important;
    }
    
    .day-number { position: relative !important; z-index: 2 !important; }
    
    .status-present { background-color: #00c09d !important; color: white !important; }
    .status-absent { background-color: #ff5252 !important; color: white !important; }
    .status-late { background-color: #ffab00 !important; color: white !important; }
    .status-leave { background-color: #2196f3 !important; color: white !important; }
    .status-holiday { background-color: #f1f1f1 !important; color: #aaa !important; }
    
    .legend-item { display: inline-flex !important; align-items: center !important; margin-right: 15px !important; font-size: 0.9rem !important; }
    .legend-dot { width: 12px !important; height: 12px !important; border-radius: 50% !important; margin-right: 5px !important; }
</style>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">
                    <i class="fas fa-user-check mr-2"></i> 
                    {{ $type == 'class' ? 'ক্লাস হাজিরা রিপোর্ট' : 'এক্সট্রা ক্লাস হাজিরা রিপোর্ট' }}
                </h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <!-- Stats Section -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-box-stat bg-gradient-pink">
                <div class="stat-number">{{ $stats['working_days'] }}</div>
                <div class="stat-label">Working Days</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box-stat bg-gradient-green">
                <div class="stat-number">{{ $stats['present'] + $stats['late'] }}</div>
                <div class="stat-label">Present ({{ $stats['working_days'] > 0 ? round((($stats['present'] + $stats['late']) / $stats['working_days']) * 100) : 0 }}%)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box-stat bg-gradient-orange">
                <div class="stat-number">{{ $stats['absent'] }}</div>
                <div class="stat-label">Absent ({{ $stats['working_days'] > 0 ? round(($stats['absent'] / $stats['working_days']) * 100) : 0 }}%)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box-stat bg-gradient-blue">
                <div class="stat-number">{{ $stats['leave'] }}</div>
                <div class="stat-label">Leave ({{ $stats['working_days'] > 0 ? round(($stats['leave'] / $stats['working_days']) * 100) : 0 }}%)</div>
            </div>
        </div>
    </div>

    <!-- Calendar Card -->
    <div class="card attendance-card">
        <div class="card-header border-0 bg-transparent py-4">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="far fa-calendar-alt text-primary mr-2"></i> Attendance
                </h3>
                <div class="calendar-nav d-flex align-items-center">
                    <a href="{{ route($type == 'class' ? 'parent.attendance.class' : 'parent.attendance.extra', ['student_id' => $selectedStudent->id, 'month' => $prevMonth->month, 'year' => $prevMonth->year]) }}" class="btn btn-sm btn-outline-secondary rounded-circle mr-3">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <h5 class="mb-0 font-weight-bold">{{ $monthName }}</h5>
                    <a href="{{ route($type == 'class' ? 'parent.attendance.class' : 'parent.attendance.extra', ['student_id' => $selectedStudent->id, 'month' => $nextMonth->month, 'year' => $nextMonth->year]) }}" class="btn btn-sm btn-outline-secondary rounded-circle ml-3">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="calendar-grid">
                <div class="calendar-header">Sun</div>
                <div class="calendar-header">Mon</div>
                <div class="calendar-header">Tue</div>
                <div class="calendar-header">Wed</div>
                <div class="calendar-header">Thu</div>
                <div class="calendar-header">Fri</div>
                <div class="calendar-header">Sat</div>

                @php
                    $firstDayOfMonth = \Carbon\Carbon::createFromDate($year, $month, 1);
                    $blankCells = $firstDayOfMonth->dayOfWeek; // 0 for Sun, 1 for Mon, etc.
                @endphp

                @for($i = 0; $i < $blankCells; $i++)
                    <div></div>
                @endfor

                @foreach($calendar as $dateStr => $data)
                    <div class="calendar-day {{ $data['is_holiday'] ? 'status-holiday' : '' }} status-{{ $data['status'] }}" 
                         title="{{ $data['remarks'] }}"
                         data-toggle="tooltip">
                        <span class="day-number">{{ $data['day'] }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Legend -->
            <div class="mt-5 text-center">
                <div class="legend-item"><span class="legend-dot status-present"></span> উপস্থিত</div>
                <div class="legend-item"><span class="legend-dot status-absent"></span> অনুপস্থিত</div>
                <div class="legend-item"><span class="legend-dot status-late"></span> দেরিতে</div>
                <div class="legend-item"><span class="legend-dot status-leave"></span> ছুটি</div>
                <div class="legend-item"><span class="legend-dot status-holiday" style="background-color: #f1f1f1"></span> বন্ধ</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        if (typeof $().tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }
    })
</script>
@endpush
