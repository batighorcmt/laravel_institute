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
                <a href="{{ route('principal.institute.students.print-cv', [$school, $student]) }}" target="_blank" class="btn btn-light btn-sm mr-2">
                    <i class="fas fa-print mr-1"></i> প্রোফাইল প্রিন্ট
                </a>
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
                                <i class="fas fa-phone mr-1"></i> {{ $activeEnrollment->section->classTeacher?->phone ?? 'N/A' }}
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
                        <div class="stats-number">{{ $workingDays }}</div>
                        <div class="stats-label">Working<br>Days</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stats-card green">
                        <div class="stats-number">{{ $attendanceStats['present'] }}</div>
                        <div class="stats-label">Present<br>({{ $workingDays ? round($attendanceStats['present']/$workingDays*100) : 0 }}%)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stats-card orange">
                        <div class="stats-number">{{ $attendanceStats['absent'] }}</div>
                        <div class="stats-label">Absent<br>({{ $workingDays ? round($attendanceStats['absent']/$workingDays*100) : 0 }}%)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stats-card blue">
                        <div class="stats-number">{{ $attendanceStats['leave'] }}</div>
                        <div class="stats-label">Leave<br>({{ $workingDays ? round($attendanceStats['leave']/$workingDays*100) : 0 }}%)</div>
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
                        <a href="{{ route('principal.institute.students.show', [$school, $student, 'month' => $carbonDate->copy()->subMonth()->month, 'year' => $carbonDate->copy()->subMonth()->year]) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span class="mx-3 font-weight-bold">{{ $carbonDate->translatedFormat('F Y') }}</span>
                        <a href="{{ route('principal.institute.students.show', [$school, $student, 'month' => $carbonDate->copy()->addMonth()->month, 'year' => $carbonDate->copy()->addMonth()->year]) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-chevron-right"></i>
                        </a>
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
                        $daysInMonth = $carbonDate->daysInMonth;
                        $firstDayOfMonth = $carbonDate->dayOfWeek; // 0 (Sun) to 6 (Sat)
                        $dayCount = 1;
                    @endphp
                    @for($i = 0; $i < 6; $i++)
                        <div class="row text-center mb-1">
                            @for($j = 0; $j < 7; $j++)
                                <div class="col p-1">
                                    @if(($i == 0 && $j < $firstDayOfMonth) || $dayCount > $daysInMonth)
                                        <div style="height: 35px;"></div>
                                    @else
                                        @php
                                            $currentDate = $carbonDate->copy()->day($dayCount);
                                            $isHoliday = isset($holidays[$dayCount]);
                                            $isWeeklyHoliday = in_array($j, $weeklyHolidays);
                                            $attendance = $attendances[$dayCount] ?? null;
                                            
                                            $statusClass = 'day-holiday';
                                            if ($attendance) {
                                                $statusClass = 'day-'.$attendance->status;
                                            } elseif ($isHoliday || $isWeeklyHoliday) {
                                                $statusClass = 'day-holiday';
                                            } else {
                                                $statusClass = ''; // Default gray/empty
                                            }
                                            
                                            $canClick = ($attendance && $attendance->status == 'present');
                                        @endphp
                                        <div class="calendar-day {{ $statusClass }}" 
                                             style="{{ $canClick ? 'cursor: pointer;' : '' }}"
                                             @if($canClick) onclick="showEvaluation('{{ $currentDate->format('Y-m-d') }}')" @endif>
                                            {{ $dayCount }}
                                        </div>
                                        @php $dayCount++; @endphp
                                    @endif
                                </div>
                            @endfor
                        </div>
                        @if($dayCount > $daysInMonth) @break @endif
                    @endfor
                </div>

                <div class="mt-3 d-flex justify-content-center flex-wrap small text-muted">
                    <span class="mr-3"><span class="badge day-present" style="width: 12px; height: 12px; display: inline-block;"></span> উপস্থিত</span>
                    <span class="mr-3"><span class="badge day-absent" style="width: 12px; height: 12px; display: inline-block;"></span> অনুপস্থিত</span>
                    <span class="mr-3"><span class="badge day-late" style="width: 12px; height: 12px; display: inline-block;"></span> দেরিতে</span>
                    <span class="mr-3"><span class="badge day-leave" style="width: 12px; height: 12px; display: inline-block;"></span> ছুটি</span>
                    <span><span class="badge day-holiday" style="width: 12px; height: 12px; display: inline-block;"></span> বন্ধ</span>
                </div>
            </div>


            <!-- Enrollment History -->
            @if(isset($enrollments) && count($enrollments) > 0)
                <div class="profile-info-card mb-3">
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

            <!-- Subject-wise Evaluation Summary -->
            @if(isset($subjectStats) && $subjectStats->count() > 0)
                <div class="profile-info-card mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 font-weight-bold text-primary">
                            <i class="fas fa-clipboard-check mr-2"></i> লেসন ইভেলুশন রিপোর্ট ({{ $currentYear?->name }})
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                            <thead class="thead-light text-center">
                                <tr>
                                    <th class="text-left">বিষয়ের নাম</th>
                                    <th class="text-left">শিক্ষকের নাম</th>
                                    <th>পড়া হয়েছে</th>
                                    <th>আংশিক হয়েছে</th>
                                    <th>হয় নাই</th>
                                    <th>অনুপস্থিত</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @foreach($subjectStats as $stat)
                                    <tr>
                                        <td class="text-left font-weight-bold">{{ $stat['subject'] }}</td>
                                        <td class="text-left small">{{ $stat['teacher'] }}</td>
                                        <td><span class="badge badge-success px-2">{{ $stat['completed'] }}</span></td>
                                        <td><span class="badge badge-warning px-2">{{ $stat['partial'] }}</span></td>
                                        <td><span class="badge badge-danger px-2">{{ $stat['not_done'] }}</span></td>
                                        <td><span class="badge badge-secondary px-2">{{ $stat['absent'] }}</span></td>
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

<!-- Lesson Evaluation Modal -->
<div class="modal fade" id="evaluationModal" tabindex="-1" role="dialog" aria-labelledby="evaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title" id="evaluationModalLabel">
                    <i class="fas fa-book-reader mr-2"></i> লেসন ইভেলুশন রিপোর্ট (<span id="evalDate"></span>)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>বিষয়ের নাম</th>
                                <th>শিক্ষকের নাম</th>
                                <th class="text-center">স্ট্যাটাস</th>
                            </tr>
                        </thead>
                        <tbody id="evaluationBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 p-3 bg-light rounded" id="evaluationSummary">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h5 mb-0 text-success font-weight-bold" id="totalRead">0</div>
                            <div class="small text-muted">মোট পড়া হয়েছে</div>
                        </div>
                        <div class="col-6">
                            <div class="h5 mb-0 text-danger font-weight-bold" id="totalNotRead">0</div>
                            <div class="small text-muted">পড়া হয় নাই</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function showEvaluation(date) {
        $('#evalDate').text(date);
        $('#evaluationBody').html('<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i> লোড হচ্ছে...</td></tr>');
        $('#totalRead').text('0');
        $('#totalNotRead').text('0');
        $('#evaluationModal').modal('show');

        const url = `{{ route('principal.institute.students.lesson-evaluation-details', [$school, $student]) }}?date=${date}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.evaluations.length === 0) {
                    html = '<tr><td colspan="3" class="text-center text-muted">কোন তথ্য পাওয়া যায়নি</td></tr>';
                } else {
                    data.evaluations.forEach(ev => {
                        const statusColor = ev.status_raw === 'completed' ? 'success' : (ev.status_raw === 'partial' ? 'warning' : 'danger');
                        html += `
                            <tr>
                                <td class="font-weight-bold">${ev.subject}</td>
                                <td>${ev.teacher}</td>
                                <td class="text-center">
                                    <span class="badge badge-${statusColor} badge-modern">${ev.status}</span>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#evaluationBody').html(html);
                $('#totalRead').text(data.summary.completed);
                $('#totalNotRead').text(data.summary.not_done);
            })
            .catch(error => {
                $('#evaluationBody').html('<tr><td colspan="3" class="text-center text-danger">তথ্য লোড করতে সমস্যা হয়েছে</td></tr>');
                console.error('Error:', error);
            });
    }
</script>
@endpush
@endsection
