@extends('layouts.admin')

@section('title', 'Lesson Evaluation Details')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Lesson Evaluation Details</h3>
            <a href="{{ route('principal.institute.lesson-evaluations.index', $school) }}" class="btn btn-secondary btn-sm">Back to Reports</a>
        </div>
        <div class="card-body">
            <div class="mb-2">
                <div class="text-muted small">Date: {{ optional($lessonEvaluation->evaluation_date)->format('Y-m-d') }} | Teacher: {{ optional($lessonEvaluation->teacher)->full_name ?? optional($lessonEvaluation->teacher->user)->name }}</div>
            </div>
            <div class="mb-3">
                <strong>Class:</strong> {{ optional($lessonEvaluation->class)->name ?? '-' }}
                &nbsp; <strong>Section:</strong> {{ optional($lessonEvaluation->section)->name ?? '-' }}
                &nbsp; <strong>Subject:</strong> {{ optional($lessonEvaluation->subject)->name ?? '-' }}।
            </div>

            <div class="mb-3">
                <strong>Stats:</strong>
                <small>Total: {{ $stats['total'] }} • Done: {{ $stats['completed'] }} • Partial: {{ $stats['partial'] }} • Not Done: {{ $stats['not_done'] }} • Absent: {{ $stats['absent'] }} • Completion: {{ $stats['completion_rate'] }}%</small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th style="width:64px">Photo</th>
                            <th>Roll</th>
                            <th>Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lessonEvaluation->records as $record)
                            <tr>
                                <td>
                                    @if($record->student)
                                            <img src="{{ $record->student->photo_url ?? asset('images/default-avatar.svg') }}" alt="photo" class="img-thumbnail" style="width:48px;height:48px;object-fit:cover">
                                        @else
                                            <div class="bg-light border text-center" style="width:48px;height:48px;line-height:48px">-</div>
                                        @endif
                                </td>
                                <td>{{ optional($record->student)->roll ?? '-' }}</td>
                                <td>{{ optional($record->student)->full_name ?? ('Student #' . $record->student_id) }}</td>
                                <td><span class="badge badge-{{ $record->status_color }}">{{ $record->status_label }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
