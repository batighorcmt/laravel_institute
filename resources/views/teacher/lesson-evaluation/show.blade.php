@extends('layouts.admin')

@section('title', 'লেসন মূল্যায়ন বিস্তারিত')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">লেসন মূল্যায়ন বিস্তারিত</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teacher.institute.lesson-evaluation.index', $school) }}">লেসন ইভেলুয়েশন</a></li>
                    <li class="breadcrumb-item active">বিস্তারিত</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    মূল্যায়ন তথ্য
                </h3>
                <div class="card-tools">
                    <a href="{{ route('teacher.institute.lesson-evaluation.index', $school) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left mr-1"></i>
                        ফিরে যান
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>শ্রেণি:</strong><br>
                        <span class="badge badge-lg badge-info">{{ $lessonEvaluation->class->name }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>শাখা:</strong><br>
                        <span class="badge badge-lg badge-info">{{ $lessonEvaluation->section->name }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>বিষয়:</strong><br>
                        {{ $lessonEvaluation->subject->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>শিক্ষক:</strong><br>
                        {{ trim(($teacher->first_name_bn ?? $teacher->first_name) . ' ' . ($teacher->last_name_bn ?? $teacher->last_name ?? '')) }}
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <strong>তারিখ:</strong><br>
                        {{ $lessonEvaluation->evaluation_date->format('d/m/Y') }}
                    </div>
                    <div class="col-md-3">
                        <strong>সময়:</strong><br>
                        {{ $lessonEvaluation->evaluation_time ? \Carbon\Carbon::parse($lessonEvaluation->evaluation_time)->format('h:i A') : 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>পিরিয়ড:</strong><br>
                        {{ $lessonEvaluation->routineEntry ? 'পিরিয়ড ' . $lessonEvaluation->routineEntry->period_number : 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>সংরক্ষণের সময়:</strong><br>
                        {{ $lessonEvaluation->created_at->format('d/m/Y h:i A') }}
                    </div>
                </div>
                @if($lessonEvaluation->notes)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>মন্তব্য/নোট:</strong><br>
                            <p class="text-muted">{{ $lessonEvaluation->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">পড়া হয়েছে</span>
                        <span class="info-box-number">{{ $stats['completed'] }} জন</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $stats['total'] > 0 ? ($stats['completed'] / $stats['total'] * 100) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $stats['total'] > 0 ? round($stats['completed'] / $stats['total'] * 100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-adjust"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">আংশিক হয়েছে</span>
                        <span class="info-box-number">{{ $stats['partial'] }} জন</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $stats['total'] > 0 ? ($stats['partial'] / $stats['total'] * 100) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $stats['total'] > 0 ? round($stats['partial'] / $stats['total'] * 100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">পড়া হয়নি</span>
                        <span class="info-box-number">{{ $stats['not_done'] }} জন</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $stats['total'] > 0 ? ($stats['not_done'] / $stats['total'] * 100) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $stats['total'] > 0 ? round($stats['not_done'] / $stats['total'] * 100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-secondary">
                    <span class="info-box-icon"><i class="fas fa-user-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">অনুপস্থিত</span>
                        <span class="info-box-number">{{ $stats['absent'] }} জন</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $stats['total'] > 0 ? ($stats['absent'] / $stats['total'] * 100) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $stats['total'] > 0 ? round($stats['absent'] / $stats['total'] * 100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title">
                    <i class="fas fa-users mr-2"></i>
                    শিক্ষার্থীদের মূল্যায়ন রেকর্ড
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="10%">রোল</th>
                                <th width="60%">শিক্ষার্থীর নাম</th>
                                <th width="30%" class="text-center">অবস্থা</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lessonEvaluation->records as $record)
                                @php
                                    $student = $record->student;
                                    $enrollment = $student->enrollments()->where('class_id', $lessonEvaluation->class_id)
                                                        ->where('section_id', $lessonEvaluation->section_id)
                                                        ->first();
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge badge-secondary badge-lg">{{ $enrollment->roll_no ?? 'N/A' }}</span>
                                    </td>
                                    <td><strong>{{ $student->student_name_en }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $record->status_color }} badge-lg">
                                            @if($record->status === 'completed')
                                                <i class="fas fa-check-circle mr-1"></i>
                                            @elseif($record->status === 'partial')
                                                <i class="fas fa-adjust mr-1"></i>
                                            @elseif($record->status === 'not_done')
                                                <i class="fas fa-times-circle mr-1"></i>
                                            @else
                                                <i class="fas fa-user-slash mr-1"></i>
                                            @endif
                                            {{ $record->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">কোন রেকর্ড নেই</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
