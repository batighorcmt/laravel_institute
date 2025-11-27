@extends('layouts.admin')

@section('title', 'Lesson Evaluation Details')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Lesson Evaluation Details</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teacher.institute.lesson-evaluation.index', $school) }}">Lesson Evaluation</a></li>
                    <li class="breadcrumb-item active">Details</li>
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
                    Evaluation Info
                </h3>
                <div class="card-tools">
                    <a href="{{ route('teacher.institute.lesson-evaluation.index', $school) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Class:</strong><br>
                        <span class="badge badge-lg badge-info">{{ $lessonEvaluation->class->name }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Section:</strong><br>
                        <span class="badge badge-lg badge-info">{{ $lessonEvaluation->section->name }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Subject:</strong><br>
                        {{ $lessonEvaluation->subject->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Teacher:</strong><br>
                        {{ trim(($teacher->first_name_bn ?? $teacher->first_name) . ' ' . ($teacher->last_name_bn ?? $teacher->last_name ?? '')) }}
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <strong>Date:</strong><br>
                        {{ $lessonEvaluation->evaluation_date->format('d/m/Y') }}
                    </div>
                    <div class="col-md-3">
                        <strong>Time:</strong><br>
                        {{ $lessonEvaluation->evaluation_time ? \Carbon\Carbon::parse($lessonEvaluation->evaluation_time)->format('h:i A') : 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Period:</strong><br>
                        {{ $lessonEvaluation->routineEntry ? 'Period ' . $lessonEvaluation->routineEntry->period_number : 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Saved At:</strong><br>
                        {{ $lessonEvaluation->created_at->format('d/m/Y h:i A') }}
                    </div>
                </div>
                @if($lessonEvaluation->notes)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Notes:</strong><br>
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
                        <span class="info-box-text">Completed</span>
                        <span class="info-box-number">{{ $stats['completed'] }}</span>
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
                        <span class="info-box-text">Partial</span>
                        <span class="info-box-number">{{ $stats['partial'] }}</span>
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
                        <span class="info-box-text">Not Done</span>
                        <span class="info-box-number">{{ $stats['not_done'] }}</span>
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
                        <span class="info-box-text">Absent</span>
                        <span class="info-box-number">{{ $stats['absent'] }}</span>
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
                    Student Evaluation Records
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="10%">Roll</th>
                                <th width="60%">Student Name</th>
                                <th width="30%" class="text-center">Status</th>
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
                                    <td colspan="4" class="text-center text-muted">No records</td>
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
