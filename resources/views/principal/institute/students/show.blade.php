@extends('layouts.admin')
@section('title','শিক্ষার্থী প্রোফাইল')

@push('styles')
<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px; 
    padding: 30px;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}
.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 5px solid rgba(255,255,255,0.3);
    object-fit: cover;
    background: white;
}
.profile-info-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.profile-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}
.info-label {
    color: #6c757d;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 5px;
}
.info-value {
    color: #2d3748;
    font-weight: 600;
    font-size: 1rem;
}
.stats-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 12px;
    padding: 20px;
    color: white;
    text-align: center;
    box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
}
.stats-card.blue {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}
.stats-card.green {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}
.stats-card.orange {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}
.stats-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}
.stats-label {
    font-size: 0.9rem;
    opacity: 0.9;
}
.guardian-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 20px;
    color: white;
    margin-bottom: 15px;
}
.attendance-calendar {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.calendar-day {
    width: 35px;
    height: 35px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    margin: 2px;
    font-size: 0.85rem;
    font-weight: 600;
}
.day-present { background: #10b981; color: white; }
.day-absent { background: #ef4444; color: white; }
.day-late { background: #f59e0b; color: white; }
.day-leave { background: #3b82f6; color: white; }
.day-holiday { background: #f3f4f6; color: #9ca3af; }
.fee-status {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.fee-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}
.badge-modern {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}
.notice-card {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 10px;
    border-left: 4px solid #f59e0b;
}
.event-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 10px;
    border-left: 4px solid #667eea;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.chart-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    height: 100%;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Profile Header -->
    <div class="profile-header mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <img src="{{ $student->photo_url }}" alt="Profile" class="profile-avatar">
            </div>
            <div class="col">
                <h2 class="mb-2 font-weight-bold">{{ $student->student_name_bn ?? $student->student_name_en }}</h2>
                <div class="mb-2">
                    <span class="badge badge-light badge-modern mr-2">
                        <i class="fas fa-id-card mr-1"></i> {{ $student->student_id }}
                    </span>
                    @if($activeEnrollment)
                        <span class="badge badge-light badge-modern mr-2">
                            <i class="fas fa-graduation-cap mr-1"></i> শ্রেণি: {{ $activeEnrollment->class?->name }} - {{ $activeEnrollment->section?->name }}
                        </span>
                        <span class="badge badge-light badge-modern">
                            <i class="fas fa-hashtag mr-1"></i> রোল: {{ $activeEnrollment->roll_no }}
                        </span>
                    @endif
                </div>
                <p class="mb-0 small" style="opacity: 0.9;">
                    <i class="fas fa-calendar-alt mr-1"></i> সেশন: {{ $currentYear->name ?? 'N/A' }}
                </p>
            </div>
            <div class="col-auto text-right">
                <a href="{{ route('principal.institute.students.edit',[$school,$student]) }}" class="btn btn-light btn-sm mr-2">
                    <i class="fas fa-edit mr-1"></i> সম্পাদনা
                </a>
                <a href="{{ route('principal.institute.students.index',$school) }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> তালিকা
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-4">
            <!-- Class Teacher Card -->
            @if($activeEnrollment && $activeEnrollment->section && $activeEnrollment->section->class_teacher_name)
                <div class="guardian-card">
                    <h6 class="mb-3"><i class="fas fa-chalkboard-teacher mr-2"></i> শ্রেণি শিক্ষক</h6>
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-tie fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $activeEnrollment->section->class_teacher_name }}</h6>
                            <p class="mb-0 small" style="opacity: 0.9;">
                                <i class="fas fa-phone mr-1"></i> {{ $activeEnrollment->section->class_teacher_phone ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Guardian Info -->
            <div class="profile-info-card">
                <h6 class="mb-3 font-weight-bold text-primary">
                    <i class="fas fa-user-friends mr-2"></i> অভিভাবকের তথ্য
                </h6>
                <div class="mb-3">
                    <div class="info-label"><i class="fas fa-male mr-1"></i> পিতার নাম</div>
                    <div class="info-value">{{ $student->father_name_bn ?? $student->father_name ?? '—' }}</div>
                </div>
                <div class="mb-3">
                    <div class="info-label"><i class="fas fa-female mr-1"></i> মাতার নাম</div>
                    <div class="info-value">{{ $student->mother_name_bn ?? $student->mother_name ?? '—' }}</div>
                </div>
                @if($student->guardian_name_en || $student->guardian_name_bn)
                    <div class="mb-3">
                        <div class="info-label"><i class="fas fa-user-shield mr-1"></i> অভিভাবক</div>
                        <div class="info-value">{{ $student->guardian_name_bn ?? $student->guardian_name_en }}</div>
                        <small class="text-muted">{{ $student->guardian_relation ?? '' }}</small>
                    </div>
                @endif
                <div class="mb-3">
                    <div class="info-label"><i class="fas fa-phone mr-1"></i> মোবাইল</div>
                    <div class="info-value">{{ $student->guardian_phone ?? '—' }}</div>
                </div>
                <div class="mb-0">
                    <div class="info-label"><i class="fas fa-map-marker-alt mr-1"></i> ঠিকানা</div>
                    <div class="info-value small">
                        {{ $student->present_village ? $student->present_village.', ' : '' }}
                        {{ $student->present_upazilla ? $student->present_upazilla.', ' : '' }}
                        {{ $student->present_district ?? '' }}
                    </div>
                </div>
            </div>

            <!-- Personal Info -->
            <div class="profile-info-card">
                <h6 class="mb-3 font-weight-bold text-success">
                    <i class="fas fa-info-circle mr-2"></i> ব্যক্তিগত তথ্য
                </h6>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="info-label"><i class="fas fa-birthday-cake mr-1"></i> জন্ম তারিখ</div>
                        <div class="info-value small">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : '—' }}</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="info-label"><i class="fas fa-venus-mars mr-1"></i> লিঙ্গ</div>
                        <div class="info-value">{{ $student->gender == 'male' ? 'ছেলে' : 'মেয়ে' }}</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="info-label"><i class="fas fa-tint mr-1"></i> রক্তের গ্রুপ</div>
                        <div class="info-value">{{ $student->blood_group ?? '—' }}</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="info-label"><i class="fas fa-praying-hands mr-1"></i> ধর্ম</div>
                        <div class="info-value">{{ $student->religion ? ucfirst($student->religion) : '—' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="info-label"><i class="fas fa-calendar-check mr-1"></i> ভর্তির তারিখ</div>
                        <div class="info-value small">{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d/m/Y') : '—' }}</div>
                    </div>
                </div>
            </div>

            <!-- Previous School Info -->
            @if($student->previous_school)
                <div class="profile-info-card">
                    <h6 class="mb-3 font-weight-bold text-info">
                        <i class="fas fa-school mr-2"></i> পূর্ববর্তী বিদ্যালয়
                    </h6>
                    <div class="mb-2">
                        <div class="info-label">বিদ্যালয়ের নাম</div>
                        <div class="info-value small">{{ $student->previous_school }}</div>
                    </div>
                    @if($student->pass_year)
                        <div class="mb-2">
                            <div class="info-label">পাশের বছর</div>
                            <div class="info-value">{{ $student->pass_year }}</div>
                        </div>
                    @endif
                    @if($student->previous_result)
                        <div>
                            <div class="info-label">ফলাফল</div>
                            <div class="info-value">{{ $student->previous_result }}</div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Middle Column -->
        <div class="col-lg-5">
            <!-- Stats Cards -->
            <div class="row mb-3">
                <div class="col-6 col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number">16</div>
                        <div class="stats-label">Working<br>Days</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stats-card green">
                        <div class="stats-number">10</div>
                        <div class="stats-label">Present<br>(63%)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stats-card orange">
                        <div class="stats-number">6</div>
                        <div class="stats-label">Absent<br>(38%)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stats-card blue">
                        <div class="stats-number">0</div>
                        <div class="stats-label">Leave<br>(0%)</div>
                    </div>
                </div>
            </div>

            <!-- Attendance Calendar -->
            <div class="attendance-calendar mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 font-weight-bold">
                        <i class="fas fa-calendar-alt mr-2 text-primary"></i> Attendance
                    </h6>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="mx-3 font-weight-bold">October 2024</span>
                        <button class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="btn btn-sm btn-primary ml-2">View All</button>
                    </div>
                </div>
                
                <div class="mb-2">
                    <div class="row text-center small font-weight-bold text-muted">
                        <div class="col">Sun</div>
                        <div class="col">Mon</div>
                        <div class="col">Tue</div>
                        <div class="col">Wed</div>
                        <div class="col">Thu</div>
                        <div class="col">Fri</div>
                        <div class="col">Sat</div>
                    </div>
                </div>
                
                <div>
                    @php
                        $calendar = [
                            ['', '', '', '', '', '', 1],
                            [2, 3, 4, 5, 6, 7, 8],
                            [9, 10, 11, 12, 13, 14, 15],
                            [16, 17, 18, 19, 20, 21, 22],
                            [23, 24, 25, 26, 27, 28, 29],
                            [30, 31, '', '', '', '', '']
                        ];
                        $statuses = [
                            1 => 'present', 2 => 'present', 3 => 'absent', 4 => 'holiday', 5 => 'holiday',
                            6 => 'present', 7 => 'present', 8 => 'present', 9 => 'late', 10 => 'present',
                            11 => 'holiday', 12 => 'holiday', 13 => 'present', 14 => 'present', 15 => 'leave',
                            16 => 'present', 17 => 'present', 18 => 'holiday', 19 => 'holiday', 20 => 'present',
                            21 => 'present', 22 => 'present', 23 => 'present', 24 => 'absent', 25 => 'holiday',
                            26 => 'holiday', 27 => 'present', 28 => 'present', 29 => 'present', 30 => 'present', 31 => 'absent'
                        ];
                    @endphp
                    @foreach($calendar as $week)
                        <div class="row text-center mb-1">
                            @foreach($week as $day)
                                <div class="col p-1">
                                    @if($day)
                                        @php $status = $statuses[$day] ?? 'present'; @endphp
                                        <div class="calendar-day day-{{ $status }}">{{ $day }}</div>
                                    @else
                                        <div style="height: 35px;"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 d-flex justify-content-center flex-wrap small">
                    <span class="mr-3"><span class="badge day-present" style="width: 15px; height: 15px; display: inline-block;"></span> Present</span>
                    <span class="mr-3"><span class="badge day-absent" style="width: 15px; height: 15px; display: inline-block;"></span> Absent</span>
                    <span class="mr-3"><span class="badge day-late" style="width: 15px; height: 15px; display: inline-block;"></span> Late</span>
                    <span class="mr-3"><span class="badge day-leave" style="width: 15px; height: 15px; display: inline-block;"></span> Leave</span>
                    <span><span class="badge day-holiday" style="width: 15px; height: 15px; display: inline-block;"></span> Holiday</span>
                </div>
            </div>

            <!-- Pie Chart -->
            <div class="chart-container mb-3">
                <h6 class="mb-3 font-weight-bold">
                    <i class="fas fa-chart-pie mr-2 text-success"></i> Attendance Statistics
                </h6>
                <canvas id="attendanceChart" height="180"></canvas>
            </div>

            <!-- Enrollment History -->
            @if(isset($enrollments) && count($enrollments) > 0)
                <div class="profile-info-card">
                    <h6 class="mb-3 font-weight-bold text-primary">
                        <i class="fas fa-history mr-2"></i> ভর্তির ইতিহাস
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>শিক্ষাবর্ষ</th>
                                    <th>শ্রেণি</th>
                                    <th>শাখা</th>
                                    <th>রোল</th>
                                    <th>গ্রুপ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $enroll)
                                    <tr class="{{ isset($activeEnrollment) && $enroll->id == $activeEnrollment->id ? 'table-success' : '' }}">
                                        <td>{{ $enroll->academicYear?->name ?? '—' }}</td>
                                        <td>{{ $enroll->class?->name ?? '—' }}</td>
                                        <td>{{ $enroll->section?->name ?? '—' }}</td>
                                        <td><strong>{{ $enroll->roll_no }}</strong></td>
                                        <td>{{ $enroll->group?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="col-lg-3">
            <!-- Notice/Events -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 font-weight-bold">
                        <i class="fas fa-bell mr-2 text-warning"></i> Notice/Events
                    </h6>
                    <a href="#" class="text-primary small font-weight-bold">View All</a>
                </div>
                
                <div class="notice-card">
                    <div class="d-flex align-items-start">
                        <div class="mr-3">
                            <div class="badge badge-warning" style="width: 45px; padding: 8px 0; text-align: center;">
                                <div style="font-size: 0.7rem;">NOV</div>
                                <div style="font-size: 1.2rem; font-weight: 700; line-height: 1;">10</div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 font-weight-bold small">বার্ষিক ক্রীড়া প্রতিযোগিতা</h6>
                            <p class="mb-0 small text-muted">
                                <i class="fas fa-calendar mr-1"></i> Published on 01.11.2024
                            </p>
                        </div>
                    </div>
                </div>

                <div class="event-card">
                    <div class="d-flex align-items-start">
                        <div class="mr-3">
                            <div class="badge badge-primary" style="width: 45px; padding: 8px 0; text-align: center;">
                                <div style="font-size: 0.7rem;">NOV</div>
                                <div style="font-size: 1.2rem; font-weight: 700; line-height: 1;">05</div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 font-weight-bold small">৯ম শিক্ষার্থীর প্রাক-নির্বাচন পরীক্ষার সময়সূচী</h6>
                            <p class="mb-0 small text-muted">
                                <i class="fas fa-calendar mr-1"></i> Published on 01.11.2024
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dues/Fees -->
            <div class="fee-status mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 font-weight-bold">
                        <i class="fas fa-money-bill-wave mr-2 text-danger"></i> Dues
                    </h6>
                    <button class="btn btn-sm btn-danger">
                        <i class="fas fa-hand-holding-usd mr-1"></i> Pay Now
                    </button>
                </div>

                <div class="fee-item">
                    <div>
                        <div class="font-weight-bold small">No.</div>
                        <div class="text-muted small">1</div>
                    </div>
                    <div>
                        <div class="font-weight-bold small">Account</div>
                        <div class="text-muted small">Tuition Fee</div>
                    </div>
                    <div>
                        <div class="font-weight-bold small">Details</div>
                        <div class="text-muted small">Dec 2024</div>
                    </div>
                    <div class="text-right">
                        <div class="font-weight-bold small">Due</div>
                        <div class="text-danger font-weight-bold">1,800</div>
                    </div>
                </div>

                <div class="fee-item">
                    <div>
                        <div class="font-weight-bold small">No.</div>
                        <div class="text-muted small">2</div>
                    </div>
                    <div>
                        <div class="font-weight-bold small">Account</div>
                        <div class="text-muted small">3rd Terminal</div>
                    </div>
                    <div>
                        <div class="font-weight-bold small">Details</div>
                        <div class="text-muted small">2024</div>
                    </div>
                    <div class="text-right">
                        <div class="font-weight-bold small">Due</div>
                        <div class="text-danger font-weight-bold">700</div>
                    </div>
                </div>

                <div class="fee-item border-0 pt-3">
                    <div class="col"></div>
                    <div class="col"></div>
                    <div class="col text-right">
                        <div class="font-weight-bold text-danger h6 mb-0">Total</div>
                    </div>
                    <div class="col text-right">
                        <div class="text-danger font-weight-bold h5 mb-0">2,500</div>
                    </div>
                </div>
            </div>

            <!-- Teams/Activities -->
            @if(isset($memberships) && count($memberships) > 0)
                <div class="profile-info-card">
                    <h6 class="mb-3 font-weight-bold text-success">
                        <i class="fas fa-users mr-2"></i> দল ও কার্যক্রম
                    </h6>
                    @foreach($memberships as $team)
                        <div class="d-flex align-items-center mb-2 p-2" style="background: #f8f9fa; border-radius: 8px;">
                            <div class="mr-2">
                                <i class="fas fa-user-friends text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold small">{{ $team->name }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    Joined: {{ $team->pivot->joined_at ? \Carbon\Carbon::parse($team->pivot->joined_at)->format('d/m/Y') : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Pie Chart
    const ctx = document.getElementById('attendanceChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Leave'],
                datasets: [{
                    data: [63, 38, 0, 0],
                    backgroundColor: [
                        '#10b981',
                        '#ef4444',
                        '#f59e0b',
                        '#3b82f6'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
