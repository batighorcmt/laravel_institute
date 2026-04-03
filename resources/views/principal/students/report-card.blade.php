@extends('layouts.admin')

@section('title', 'শিক্ষার্থী রিপোর্ট কার্ড - ' . ($student->student_name_bn ?? $student->student_name_en))

@section('content')
<div class="report-card-container">
    {{-- 1. Student Basic Info with Image --}}
    <div class="premium-card profile-card">
        <div class="profile-header">
            <div class="profile-avatar ripple">
                <img src="{{ $student->photo_url }}" alt="{{ $student->full_name }}" id="student-avatar">
            </div>
            <div class="profile-info">
                <h1 class="student-name">{{ $student->student_name_bn }}</h1>
                <h3 class="student-name-en">{{ $student->student_name_en }}</h3>
                <div class="badge-row">
                    <span class="info-badge">আইডি: {{ $student->student_id }}</span>
                    <span class="info-badge success">রোল: {{ $student->currentEnrollment->roll_no ?? 'N/A' }}</span>
                </div>
                <div class="enrollment-details">
                    <div class="detail-item">
                        <i class="fas fa-school"></i>
                        <span>শ্রেণি: {{ $student->currentEnrollment->class->name_bn ?? $student->currentEnrollment->class->name }}</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-layer-group"></i>
                        <span>শাখা: {{ $student->currentEnrollment->section->name_bn ?? $student->currentEnrollment->section->name }}</span>
                    </div>
                    @if($student->currentEnrollment->group)
                    <div class="detail-item">
                        <i class="fas fa-users"></i>
                        <span>বিভাগ: {{ $student->currentEnrollment->group->name_bn ?? $student->currentEnrollment->group->name }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="action-buttons no-print">
                <button class="btn btn-premium print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> মুদ্রণ করুন
                </button>
            </div>
        </div>
    </div>

    {{-- 2. Attendance Summary --}}
    <div class="section-title">
        <i class="fas fa-calendar-check text-primary"></i> হাজিরা সারাংশ ({{ $currentYear->name_bn }})
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="summary-box glass purple">
                <div class="box-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="box-content">
                    <div class="box-label">মোট কার্যদিবস</div>
                    <div class="box-value">{{ $attendanceSummary['total_working_days'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-box glass green">
                <div class="box-icon"><i class="fas fa-check-circle"></i></div>
                <div class="box-content">
                    <div class="box-label">উপস্থিতি</div>
                    <div class="box-value">{{ $attendanceSummary['present'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-box glass red">
                <div class="box-icon"><i class="fas fa-times-circle"></i></div>
                <div class="box-content">
                    <div class="box-label">অনুপস্থিতি</div>
                    <div class="box-value">{{ $attendanceSummary['absent'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-box glass blue">
                <div class="box-icon"><i class="fas fa-percentage"></i></div>
                <div class="box-content">
                    <div class="box-label">উপস্থিতির হার</div>
                    <div class="box-value">
                        @php
                            $rate = $attendanceSummary['total_working_days'] > 0 
                                ? round(($attendanceSummary['present'] / $attendanceSummary['total_working_days']) * 100, 1) 
                                : 0;
                        @endphp
                        {{ $rate }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Breakdown (Collapsible) --}}
    <div class="no-print mt-3 text-right">
        <button class="btn btn-sm btn-link text-premium" type="button" onclick="toggleMonthlyAttendance()">
            মাসিক হাজিরা রেকর্ড দেখুন <i class="fas fa-chevron-down ml-1" id="monthlyChevron"></i>
        </button>
    </div>
    <div class="no-print" id="monthlyAttendance" style="display: none;">
        <div class="premium-card mt-2">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>মাস</th>
                        <th class="text-center">মোট দিন</th>
                        <th class="text-center">উপস্থিত</th>
                        <th class="text-center">অনুপস্থিত</th>
                        <th class="text-center">হার</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyAttendance as $month => $data)
                    <tr>
                        <td><strong>{{ $month }}</strong></td>
                        <td class="text-center">{{ $data['total'] }}</td>
                        <td class="text-center text-success">{{ $data['present'] }}</td>
                        <td class="text-center text-danger">{{ $data['absent'] }}</td>
                        <td class="text-center">
                            {{ $data['total'] > 0 ? round(($data['present'] / $data['total']) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 3. Lesson Evaluation Report --}}
    <div class="section-title mt-5">
        <i class="fas fa-chart-pie text-success"></i> লেসন ইভেলুশন রিপোর্ট
    </div>
    <div class="row">
        <div class="col-lg-4">
            <div class="premium-card h-100 evaluation-total">
                <h5 class="card-subtitle mb-4">শিক্ষাবর্ষের মোট সারাংশ</h5>
                <div class="eval-stat-list">
                    <div class="eval-stat-item">
                        <span class="dot completed"></span>
                        <span class="label">পড়া হয়েছে</span>
                        <span class="value">{{ $lessonSummary['completed'] ?? 0 }}</span>
                    </div>
                    <div class="eval-stat-item">
                        <span class="dot partial"></span>
                        <span class="label">আংশিক হয়েছে</span>
                        <span class="value">{{ $lessonSummary['partial'] ?? 0 }}</span>
                    </div>
                    <div class="eval-stat-item">
                        <span class="dot not_done"></span>
                        <span class="label">পড়া হয়নি</span>
                        <span class="value">{{ $lessonSummary['not_done'] ?? 0 }}</span>
                    </div>
                    <div class="eval-stat-item">
                        <span class="dot absent"></span>
                        <span class="label">অনুপস্থিত</span>
                        <span class="value">{{ $lessonSummary['absent'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="premium-card h-100">
                <h5 class="card-subtitle mb-4">বিষয়ভিত্তিক অবস্থা</h5>
                <div class="table-responsive">
                    <table class="table table-custom table-sm">
                        <thead>
                            <tr>
                                <th>বিষয়</th>
                                <th class="text-center">পড়া হয়েছে</th>
                                <th class="text-center">আংশিক</th>
                                <th class="text-center">হয়নি</th>
                                <th class="text-center">অনুপস্থিত</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subjectWiseEvaluation as $subject => $stats)
                            <tr>
                                <td>{{ $subject }}</td>
                                <td class="text-center"><span class="badge badge-success-soft">{{ $stats['completed'] }}</span></td>
                                <td class="text-center"><span class="badge badge-warning-soft">{{ $stats['partial'] }}</span></td>
                                <td class="text-center"><span class="badge badge-danger-soft">{{ $stats['not_done'] }}</span></td>
                                <td class="text-center"><span class="badge badge-secondary-soft">{{ $stats['absent'] }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Exams List --}}
    <div class="section-title mt-5">
        <i class="fas fa-file-invoice text-warning"></i> পরীক্ষার মার্কশীট (অবস্থাভেদে)
    </div>
    <div class="premium-card p-0 overflow-hidden">
        <div class="exam-list">
            @forelse($exams as $exam)
            <a href="{{ route('principal.institute.results.marksheet.print', [$school, $exam, $student]) }}" class="exam-item" target="_blank">
                <div class="exam-icon">
                    <i class="fas fa-poll-h"></i>
                </div>
                <div class="exam-info">
                    <div class="exam-name">{{ $exam->name_bn }}</div>
                    <div class="exam-meta">
                        <span><i class="far fa-calendar-alt"></i> {{ $exam->start_date ? $exam->start_date->format('j M, Y') : 'N/A' }}</span>
                        <span class="ml-3"><i class="fas fa-check-circle text-success"></i> সম্পন্ন</span>
                    </div>
                </div>
                <div class="exam-action">
                    <span class="btn-view-marksheet">বিস্তারিত মার্কশীট <i class="fas fa-external-link-alt ml-1"></i></span>
                </div>
            </a>
            @empty
            <div class="p-5 text-center text-muted">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <p>এই শিক্ষার্থীর জন্য এই শিক্ষাবর্ষে এখনো কোনো নির্ধারিত পরীক্ষা সম্পন্ন হয়নি।</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #4f46e5;
        --primary-soft: rgba(79, 70, 229, 0.1);
        --success: #10b981;
        --success-soft: rgba(16, 185, 129, 0.1);
        --warning: #f59e0b;
        --warning-soft: rgba(245, 158, 11, 0.1);
        --danger: #ef4444;
        --danger-soft: rgba(239, 68, 68, 0.1);
        --secondary: #6b7280;
        --secondary-soft: rgba(107, 114, 128, 0.1);
        --purple: #8b5cf6;
        --purple-soft: rgba(139, 92, 246, 0.1);
    }

    .report-card-container {
        font-family: 'Inter', 'Hind Siliguri', sans-serif;
        padding-bottom: 50px;
        color: #1f2937;
    }

    .premium-card {
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #f3f4f6;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .premium-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        overflow: hidden;
        border: 5px solid #fff;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .student-name {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: #111827;
    }

    .student-name-en {
        font-size: 1.1rem;
        color: #6b7280;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .badge-row {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .info-badge {
        padding: 0.35rem 1rem;
        background: var(--primary-soft);
        color: var(--primary);
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .info-badge.success {
        background: var(--success-soft);
        color: var(--success);
    }

    .enrollment-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #4b5563;
        font-size: 0.95rem;
    }

    .detail-item i {
        color: var(--primary);
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 2rem 0 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-bottom: 2px solid #f3f4f6;
        padding-bottom: 0.5rem;
    }

    /* Summary Boxes */
    .summary-box {
        display: flex;
        align-items: center;
        padding: 1.25rem;
        border-radius: 1rem;
        gap: 1.25rem;
        background: #fff;
        border: 1px solid rgba(255,255,255,0.3);
        margin-bottom: 1rem;
    }

    .glass {
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .summary-box.purple { background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.05)); border-left: 4px solid var(--purple); }
    .summary-box.green { background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); border-left: 4px solid var(--success); }
    .summary-box.red { background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05)); border-left: 4px solid var(--danger); }
    .summary-box.blue { background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05)); border-left: 4px solid #3b82f6; }

    .box-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .purple .box-icon { color: var(--purple); }
    .green .box-icon { color: var(--success); }
    .red .box-icon { color: var(--danger); }
    .blue .box-icon { color: #3b82f6; }

    .box-label { font-size: 0.85rem; color: #6b7280; font-weight: 500; }
    .box-value { font-size: 1.5rem; font-weight: 700; color: #111827; }

    /* Tables */
    .table-custom {
        margin-bottom: 0;
    }
    .table-custom thead th {
        background: #f9fafb;
        border-top: none;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem;
    }
    .table-custom td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid #f3f4f6;
    }

    /* Lesson Evaluation Visuals */
    .eval-stat-list {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    .eval-stat-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 1.1rem;
    }
    .dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot.completed { background: var(--success); box-shadow: 0 0 10px var(--success-soft); }
    .dot.partial { background: var(--warning); box-shadow: 0 0 10px var(--warning-soft); }
    .dot.not_done { background: var(--danger); box-shadow: 0 0 10px var(--danger-soft); }
    .dot.absent { background: var(--secondary); box-shadow: 0 0 10px var(--secondary-soft); }
    .eval-stat-item .label { flex: 1; font-weight: 500; }
    .eval-stat-item .value { font-weight: 700; color: #111827; }

    /* Exam List */
    .exam-item {
        display: flex;
        align-items: center;
        padding: 1.25rem 2rem;
        gap: 1.5rem;
        text-decoration: none !important;
        color: inherit;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s ease;
    }
    .exam-item:last-child { border-bottom: none; }
    .exam-item:hover {
        background: #f9fafb;
    }
    .exam-icon {
        width: 50px;
        height: 50px;
        background: var(--primary-soft);
        color: var(--primary);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .exam-info { flex: 1; }
    .exam-name { font-weight: 700; font-size: 1.15rem; color: #111827; margin-bottom: 0.25rem; }
    .exam-meta { font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; }
    .btn-view-marksheet {
        padding: 0.5rem 1.25rem;
        background: #fff;
        border: 1px solid var(--primary);
        color: var(--primary);
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    .exam-item:hover .btn-view-marksheet {
        background: var(--primary);
        color: #fff;
    }

    /* Print Styles - Refined for Content Visibility */
    @media print {
        /* General Layout Fixes */
        html, body {
            height: auto !important;
            overflow: visible !important;
            background: #fff !important;
            color: #000 !important;
        }

        .no-print, .main-header, .main-sidebar, .main-footer, .content-header, .btn-premium {
            display: none !important;
        }

        .wrapper, .content-wrapper, .content, .container-fluid {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            position: relative !important;
            min-height: auto !important;
            width: 100% !important;
            display: block !important;
        }

        .report-card-container {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            display: block !important;
        }

        /* Fix Bootstrap Row/Col for Print */
        .row {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
        }
        
        .col-md-3, .col-md-6, .col-lg-4, .col-lg-8 {
            width: 48% !important; /* Force items to show side-by-side or stacked as blocks */
            display: inline-block !important;
            vertical-align: top;
            float: left;
            margin-bottom: 20px;
        }

        .col-md-3 { width: 24% !important; }
        .col-md-6 { width: 49% !important; }

        /* Ensure Boxes and Cards are Visible */
        .premium-card, .summary-box {
            border: 1px solid #ccc !important;
            background: #fff !important;
            color: #000 !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important; 
            margin-bottom: 15px !important;
            box-shadow: none !important;
            break-inside: avoid;
        }

        .summary-box {
            display: flex !important;
            padding: 10px !important;
            height: 80px;
        }

        .box-icon {
            border: 1px solid #eee !important;
            background: #fff !important;
            color: #000 !important;
            visibility: visible !important;
        }

        .box-value { font-size: 16pt !important; color: #000 !important; }
        .box-label { font-size: 10pt !important; color: #333 !important; }

        .section-title {
            display: block !important;
            border-bottom: 2px solid #000 !important;
            margin: 30px 0 15px 0 !important;
            padding-bottom: 5px !important;
            color: #000 !important;
            font-size: 18pt !important;
            clear: both;
        }

        /* Table fixes */
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .table th, .table td {
            border: 1px solid #ddd !important;
            color: #000 !important;
            padding: 5px !important;
        }

        /* Profile fixes */
        .profile-header {
            display: flex !important;
            gap: 20px !important;
        }
        .profile-avatar {
            width: 100px !important;
            height: 100px !important;
        }
    }

    .btn-premium {
        background: linear-gradient(135deg, var(--primary), var(--purple));
        color: #fff;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
        transition: all 0.3s ease;
    }
    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(79, 70, 229, 0.4);
        color: #fff;
    }

    .badge-success-soft { background: var(--success-soft); color: var(--success); }
    .badge-warning-soft { background: var(--warning-soft); color: var(--warning); }
    .badge-danger-soft { background: var(--danger-soft); color: var(--danger); }
    .badge-secondary-soft { background: var(--secondary-soft); color: var(--secondary); }

    /* Animations */
    .ripple { overflow: hidden; position: relative; }
    .profile-avatar:after {
        content: ""; display: block; width: 100%; height: 100%; position: absolute; top: 0; left: 0;
        background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 70%);
        opacity: 0; transition: opacity 0.3s; pointer-events: none;
    }
    .profile-avatar:hover:after { opacity: 1; }
</style>
@endpush

@section('scripts')
<script>
    function toggleMonthlyAttendance() {
        const el = document.getElementById('monthlyAttendance');
        const icon = document.getElementById('monthlyChevron');
        if (el.style.display === 'none') {
            el.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        } else {
            el.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>
@endsection
