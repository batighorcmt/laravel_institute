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
                    <span class="info-badge">আইডি: {{ toBengaliNumber($student->student_id) }}</span>
                    <span class="info-badge success">রোল: {{ toBengaliNumber($student->currentEnrollment->roll_no ?? 'N/A') }}</span>
                </div>
                <div class="enrollment-details mt-3">
                    <div class="detail-item">
                        <i class="fas fa-school"></i>
                        <span>শ্রেণি: {{ langField($student->currentEnrollment->class, 'name', 'bn') }}</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-layer-group"></i>
                        <span>শাখা: {{ langField($student->currentEnrollment->section, 'name', 'bn') }}</span>
                    </div>
                    @if($student->currentEnrollment->group)
                    <div class="detail-item">
                        <i class="fas fa-users"></i>
                        <span>বিভাগ: {{ langField($student->currentEnrollment->group, 'name', 'bn') }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="action-buttons no-print">
                <a href="{{ route('principal.institute.students.report-cards.print', [$school, $student, 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="btn btn-premium print-btn" target="_blank">
                    <i class="fas fa-print"></i> মুদ্রণ করুন (A4)
                </a>
            </div>
        </div>
    </div>

    {{-- 2. Attendance Summary --}}
    <div class="section-title">
        <i class="fas fa-calendar-check text-primary"></i> হাজিরা সারাংশ 
        <span class="ml-2" style="font-size: 0.9rem; font-weight: 500; color: #6b7280;">
            @if($startDate && $endDate)
                ({{ toBengaliNumber($startDate->format('d/m/Y')) }} হতে {{ toBengaliNumber($endDate->format('d/m/Y')) }} পর্যন্ত)
            @else
                (সকল তারিখের তথ্য)
            @endif
        </span>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="summary-box glass purple">
                <div class="box-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="box-content">
                    <div class="box-label">মোট কার্যদিবস</div>
                    <div class="box-value">{{ toBengaliNumber($attendanceSummary['total_working_days']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-box glass green">
                <div class="box-icon"><i class="fas fa-check-circle"></i></div>
                <div class="box-content">
                    <div class="box-label">উপস্থিতি</div>
                    <div class="box-value">{{ toBengaliNumber($attendanceSummary['present']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-box glass red">
                <div class="box-icon"><i class="fas fa-times-circle"></i></div>
                <div class="box-content">
                    <div class="box-label">অনুপস্থিতি</div>
                    <div class="box-value">{{ toBengaliNumber($attendanceSummary['absent']) }}</div>
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
                        {{ toBengaliNumber($rate) }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Breakdown --}}
    <div class="mt-4">
        <div class="section-title sub-title">
            <i class="fas fa-history text-purple"></i> মাসিক হাজিরার বিস্তারিত পরিসংখ্যন
        </div>
        <div class="premium-card">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>মাস</th>
                            <th class="text-center">মোট দিন</th>
                            <th class="text-center">উপস্থিত</th>
                            <th class="text-center">অনুপস্থিত</th>
                            <th class="text-center">উপস্থিতির হার</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyAttendance as $month => $data)
                        <tr>
                            <td><strong>{{ toBengaliMonth($month) }}</strong></td>
                            <td class="text-center">{{ toBengaliNumber($data['total']) }}</td>
                            <td class="text-center text-success">{{ toBengaliNumber($data['present']) }}</td>
                            <td class="text-center text-danger">{{ toBengaliNumber($data['absent']) }}</td>
                            <td class="text-center">
                                <div class="progress-wrapper">
                                    <span class="progress-text">{{ toBengaliNumber($data['total'] > 0 ? round(($data['present'] / $data['total']) * 100, 1) : 0) }}%</span>
                                    <div class="progress-mini">
                                        <div class="progress-bar-mini" style="width: {{ $data['total'] > 0 ? ($data['present'] / $data['total']) * 100 : 0 }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 3. Lesson Evaluation Report --}}
    <div class="section-title mt-5">
        <i class="fas fa-chart-pie text-success"></i> লেসন ইভেলুশন রিপোর্ট
        <span class="ml-2" style="font-size: 0.9rem; font-weight: 500; color: #6b7280;">
            @if($startDate && $endDate)
                ({{ toBengaliNumber($startDate->format('d/m/Y')) }} হতে {{ toBengaliNumber($endDate->format('d/m/Y')) }} পর্যন্ত)
            @else
                (সকল তারিখের তথ্য)
            @endif
        </span>
    </div>
    <div class="row">
        <div class="col-lg-4">
            <div class="premium-card h-100 evaluation-total">
                <h5 class="card-subtitle mb-4">শিক্ষাবর্ষের মোট সারাংশ</h5>
                <div class="eval-stat-list">
                    <div class="eval-stat-item">
                        <span class="dot completed"></span>
                        <span class="label">পড়া হয়েছে</span>
                        <span class="value">{{ toBengaliNumber($lessonSummary['completed'] ?? 0) }}</span>
                    </div>
                    <div class="eval-stat-item">
                        <span class="dot partial"></span>
                        <span class="label">আংশিক হয়েছে</span>
                        <span class="value">{{ toBengaliNumber($lessonSummary['partial'] ?? 0) }}</span>
                    </div>
                    <div class="eval-stat-item">
                        <span class="dot not_done"></span>
                        <span class="label">পড়া হয়নি</span>
                        <span class="value">{{ toBengaliNumber($lessonSummary['not_done'] ?? 0) }}</span>
                    </div>
                    <div class="eval-stat-item">
                        <span class="dot absent"></span>
                        <span class="label">অনুপস্থিত</span>
                        <span class="value">{{ toBengaliNumber($lessonSummary['absent'] ?? 0) }}</span>
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
                                <td class="text-center"><span class="badge badge-success-soft" style="font-size: 110%;">{{ toBengaliNumber($stats['completed']) }}</span></td>
                                <td class="text-center"><span class="badge badge-warning-soft" style="font-size: 110%;">{{ toBengaliNumber($stats['partial']) }}</span></td>
                                <td class="text-center"><span class="badge badge-danger-soft" style="font-size: 110%;">{{ toBengaliNumber($stats['not_done']) }}</span></td>
                                <td class="text-center"><span class="badge badge-secondary-soft" style="font-size: 110%;">{{ toBengaliNumber($stats['absent']) }}</span></td>
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
    <div class="exam-container">
        @forelse($exams as $exam)
        @php $result = $exam->results->first(); @endphp
        <div class="premium-card p-0 mb-4 overflow-hidden exam-card">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-light-soft">
                <div>
                    <h5 class="mb-1 font-weight-bold text-dark">{{ $exam->name_bn }}</h5>
                    <div class="text-muted small">
                        <i class="far fa-calendar-alt mr-1"></i> পরীক্ষার তারিখ: {{ $exam->start_date ? toBengaliNumber($exam->start_date->format('j M, Y')) : 'N/A' }} | 
                        <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> সম্পন্ন</span>
                    </div>
                </div>
                <div class="no-print">
                    <a href="{{ route('principal.institute.results.marksheet.print', [$school, $exam, $student]) }}" class="btn btn-sm btn-premium-outline" target="_blank">
                        বিস্তারিত মার্কশীট <i class="fas fa-external-link-alt ml-1"></i>
                    </a>
                </div>
            </div>
            
            @php 
                $examData = $examsData[$exam->id] ?? null;
                $studentResult = $examData['result'] ?? null;
                $finalSubjects = $examData['finalSubjects'] ?? collect();
                
                $hasCalculatedData = ($studentResult && $finalSubjects->isNotEmpty());

                $rawPossibleMarks = 0;
                if ($studentResult) {
                    foreach($finalSubjects as $key => $fSub) {
                        $res = $studentResult->subject_results->get($key);
                        if ($res && empty($res['display_only'])) {
                            $rawPossibleMarks += ($fSub['total_full_mark'] ?? 0);
                        }
                    }
                }

                // Quick Stats Logic
                $totalMarks = $studentResult ? $studentResult->computed_total_marks : ($exam->results->first() ? $exam->results->first()->total_marks : $exam->marks->sum('total_marks'));
                $possibleMarks = $studentResult ? $rawPossibleMarks : '--';
                $gpa = $studentResult ? $studentResult->computed_gpa : ($exam->results->first() ? $exam->results->first()->gpa : ($exam->marks->isNotEmpty() ? $exam->marks->avg('grade_point') : 0));
                $grade = $studentResult ? $studentResult->computed_letter : ($exam->results->first() ? $exam->results->first()->letter_grade : ($exam->marks->isNotEmpty() ? '...' : '--'));
                $status = $studentResult ? ($studentResult->computed_letter == 'F' ? 'fail' : 'pass') : ($exam->results->first() ? ($exam->results->first()->result_status ?: ($exam->results->first()->letter_grade == 'F' ? 'fail' : 'pass')) : (@$status ?: ''));
                $classPosition = $studentResult ? $studentResult->class_position : ($exam->results->first() ? $exam->results->first()->merit_position : '?');
            @endphp
            
            @if($result || $exam->marks->isNotEmpty())
            <div class="p-4">
                {{-- Quick Stats Row --}}
                <div class="row no-gutters mb-4 border rounded p-3 bg-white shadow-xs text-center align-items-center">
                    <div class="col-md-3 border-right">
                        <div class="text-muted small uppercase">মোট নম্বর</div>
                        <div class="h5 mb-0 font-weight-bold">{{ toBengaliNumber($totalMarks) }} <span class="text-muted small">/ {{ toBengaliNumber($possibleMarks) }}</span></div>
                    </div>
                    <div class="col-md-2 border-right">
                        <div class="text-muted small uppercase">জিপিএ (GPA)</div>
                        <div class="h5 mb-0 font-weight-bold text-primary">{{ toBengaliNumber(preg_replace('/\.00$/', '', number_format($gpa, 2))) }}</div>
                    </div>
                    <div class="col-md-2 border-right">
                        <div class="text-muted small uppercase">গ্রেড (Grade)</div>
                        <div class="h5 mb-0 font-weight-bold {{ ($result && $result->letter_grade == 'F') || (!$result && @$status == 'fail') ? 'text-danger' : 'text-success' }}">{{ $grade }}</div>
                    </div>
                    <div class="col-md-3 border-right">
                        <div class="text-muted small uppercase">শ্রেণি অবস্থান</div>
                        <div class="h5 mb-0 font-weight-bold">{{ toBengaliNumber($classPosition) }}তম</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small uppercase">ফলাফল</div>
                        <div>
                            @if($status)
                            <span class="badge {{ $status == 'pass' ? 'badge-success' : 'badge-danger' }} p-2 px-3">
                                {{ $status == 'pass' ? 'কৃতকার্য' : 'অকৃতকার্য' }}
                            </span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Subject-wise marks table --}}
                <div class="mt-3">
                    <div class="small font-weight-bold mb-3 text-muted uppercase tracking-wider">
                        <i class="fas fa-list-ul mr-1 text-primary"></i> বিষয়ভিত্তিক প্রাপ্ত নম্বর ও জিপিএ
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-sm border rounded">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="py-2 pl-3">বিষয়</th>
                                    <th class="text-center py-2">প্রাপ্ত নম্বর</th>
                                    <th class="text-center py-2">লেটার গ্রেড</th>
                                    <th class="text-center py-2">গ্রেড পয়েন্ট (GPA)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($hasCalculatedData)
                                    @php
                                        $mainSubjects = collect();
                                        $optionalSubject = null;
                                        foreach($finalSubjects as $key => $fSub) {
                                            $res = $studentResult->subject_results->get($key);
                                            if (!$res) continue;
                                            if (!empty($res['is_optional'])) { $optionalSubject = ['key' => $key, 'fSub' => $fSub, 'res' => $res]; }
                                            else { $mainSubjects->push(['key' => $key, 'fSub' => $fSub, 'res' => $res]); }
                                        }
                                    @endphp

                                    {{-- Main Subjects --}}
                                    @foreach($mainSubjects as $item)
                                        @php 
                                            $fSub = $item['fSub']; $res = $item['res'];
                                            $isPart = !empty($res['display_only']);
                                        @endphp
                                        <tr class="{{ $isPart ? 'bg-light font-italic' : '' }}">
                                            <td class="pl-3 font-weight-500">
                                                @if($isPart) <span class="ml-3 text-muted">↳</span> @endif
                                                {{ $res['name'] ?? $fSub['name'] }}
                                            </td>
                                            <td class="text-center font-weight-bold">
                                                {{ toBengaliNumber($res['total'] > 0 ? preg_replace('/\.00$/', '', number_format($res['total'], 2)) : '০') }}
                                                @if(!$isPart) <small class="text-muted">/ {{ toBengaliNumber($fSub['total_full_mark']) }}</small> @endif
                                            </td>
                                            <td class="text-center">
                                                @if(!$isPart)
                                                    <span class="badge {{ $res['grade'] == 'F' ? 'badge-danger-soft' : 'badge-success-soft' }} px-2">
                                                        {{ $res['grade'] }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-center font-weight-bold text-primary">
                                                {{ !$isPart ? toBengaliNumber(preg_replace('/\.00$/', '', number_format($res['gpa'] ?? 0, 2))) : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Optional Subject --}}
                                    @if($optionalSubject)
                                        @php 
                                            $fSub = $optionalSubject['fSub']; $res = $optionalSubject['res'];
                                        @endphp
                                        <tr class="bg-premium-light">
                                            <td class="pl-3 font-weight-500">
                                                {{ $res['name'] ?? $fSub['name'] }} <span class="badge badge-info ml-1">ঐচ্ছিক</span>
                                            </td>
                                            <td class="text-center font-weight-bold">
                                                {{ toBengaliNumber($res['total'] > 0 ? preg_replace('/\.00$/', '', number_format($res['total'], 2)) : '০') }}
                                                <small class="text-muted">/ {{ toBengaliNumber($fSub['total_full_mark']) }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $res['grade'] == 'F' ? 'badge-danger-soft' : 'badge-success-soft' }} px-2">
                                                    {{ $res['grade'] }}
                                                </span>
                                            </td>
                                            <td class="text-center font-weight-bold text-primary">
                                                {{ toBengaliNumber(preg_replace('/\.00$/', '', number_format($res['gpa'] ?? 0, 2))) }}
                                            </td>
                                        </tr>
                                    @endif

                                @else
                                    {{-- Fallback --}}
                                    @foreach($exam->marks as $mark)
                                    <tr>
                                        <td class="pl-3 font-weight-500">{{ langField($mark->subject, 'name', 'bn') }}</td>
                                        <td class="text-center font-weight-bold">{{ toBengaliNumber(preg_replace('/\.00$/', '', number_format($mark->total_marks ?? 0, 2))) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $mark->letter_grade == 'F' ? 'badge-danger-soft' : 'badge-success-soft' }} px-2">
                                                {{ $mark->letter_grade ?: 'N/R' }}
                                            </span>
                                        </td>
                                        <td class="text-center font-weight-bold text-primary">{{ toBengaliNumber(preg_replace('/\.00$/', '', number_format($mark->grade_point ?? 0, 2))) }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="p-4 text-center text-muted">
                <i class="fas fa-exclamation-circle mr-1"></i> এই পরীক্ষার বিস্তারিত ফলাফল এখনো প্রক্রিয়াকরণধীন।
            </div>
            @endif
        </div>
        @empty
        <div class="premium-card p-5 text-center text-muted">
            <div class="mb-3"><i class="fas fa-info-circle fa-3x text-light"></i></div>
            <p class="h5">এই শিক্ষার্থীর জন্য এই শিক্ষাবর্ষে এখনো কোনো নির্ধারিত পরীক্ষা সম্পন্ন হয়নি।</p>
        </div>
        @endforelse
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

    .sub-title {
        border-bottom: none;
        margin-top: 1rem;
        font-size: 1.1rem;
    }

    .progress-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .progress-mini {
        width: 100px;
        height: 6px;
        background: #f3f4f6;
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-bar-mini {
        height: 100%;
        background: var(--primary);
        border-radius: 4px;
    }
    .progress-text {
        font-weight: 700;
        min-width: 45px;
    }

    .exam-item-container {
        border-bottom: 1px solid #f3f4f6;
    }
    .exam-item-container:last-child {
        border-bottom: none;
    }
    .exam-item-container .exam-item {
        border-bottom: none;
    }

    /* Print Styles - Refined for Content Visibility */
    @media print {
        /* General Layout Fixes */
        html, body {
            height: auto !important;
            overflow: visible !important;
            background: #fff !important;
            color: #000 !important;
            font-size: 12pt;
        }

        .no-print, .main-header, .main-sidebar, .main-footer, .content-header, .btn-premium, .print-btn {
            display: none !important;
        }

        .wrapper, .content-wrapper, .content, .container-fluid, .report-card-container {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            display: block !important;
            width: 100% !important;
            position: static !important;
        }

        /* Ensure Cards are Visible */
        .premium-card, .summary-box {
            border: 1px solid #eee !important;
            background: #fff !important;
            color: #000 !important;
            margin-bottom: 20px !important;
            padding: 15px !important;
            break-inside: avoid;
            display: block !important;
            box-shadow: none !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .profile-header {
            display: flex !important;
            gap: 20px !important;
            align-items: center !important;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .profile-avatar {
            width: 120px !important;
            height: 120px !important;
            display: block !important;
        }

        .student-name { font-size: 20pt !important; color: #000 !important; margin: 0 !important; }
        .student-name-en { font-size: 12pt !important; color: #666 !important; margin: 5px 0 !important; }

        .badge-row { display: flex !important; gap: 10px !important; margin-top: 10px !important; }
        .info-badge { border: 1px solid #ccc !important; padding: 2px 10px !important; border-radius: 10px !important; }

        .enrollment-details { display: flex !important; flex-wrap: wrap !important; gap: 15px !important; margin-top: 10px !important; }

        /* Summary boxes in print - Use 2x2 grid */
        .row { display: flex !important; flex-wrap: wrap !important; margin: 0 -10px !important; }
        .col-md-3, .col-md-6, .col-lg-4, .col-lg-8 {
            width: 50% !important;
            padding: 0 10px !important;
            box-sizing: border-box !important;
        }
        
        .box-value { font-size: 16pt !important; color: #000 !important; font-weight: bold !important; }
        .box-label { font-size: 10pt !important; color: #333 !important; }

        .section-title {
            display: block !important;
            border-bottom: 2px solid #333 !important;
            margin: 25px 0 15px 0 !important;
            padding-bottom: 5px !important;
            color: #000 !important;
            font-size: 16pt !important;
            font-weight: bold !important;
        }

        .table { width: 100% !important; border-collapse: collapse !important; margin-top: 10px !important; }
        .table th, .table td { border: 1px solid #ddd !important; padding: 8px !important; text-align: left !important; color: #000 !important; }
        .table thead th { background: #f5f5f5 !important; }
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

    .parent-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .uppercase { text-transform: uppercase; }
    .tracking-wider { letter-spacing: 0.05em; }

    .bg-light-soft { background-color: #f8fafc; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .shadow-xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .btn-premium-outline {
        border: 1px solid var(--primary);
        color: var(--primary);
        background: #fff;
        padding: 0.4rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.2s;
    }
    .btn-premium-outline:hover {
        background: var(--primary);
        color: #fff;
    }
    .font-weight-500 { font-weight: 500; }

    .exam-card { border: 1px solid #e2e8f0; }

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
