@extends('layouts.admin')

@section('title', 'Lesson Evaluation')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Lesson Evaluation</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Lesson Evaluation</li>
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
                    <i class="fas fa-calendar-day mr-2"></i>
                    Today's Classes ({{ \Carbon\Carbon::parse($today)->format('d/m/Y') }})
                </h3>
            </div>
            <div class="card-body">
                @if($routineEntries->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        You have no classes today.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="10%">Period</th>
                                    <th width="15%">Time</th>
                                    <th width="15%">Class</th>
                                    <th width="10%">Section</th>
                                    <th width="20%">Subject</th>
                                    <th width="15%">Status</th>
                                    <th width="15%" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($routineEntries as $entry)
                                    @php
                                        $isEvaluated = in_array($entry->id, $evaluatedIds);
                                    @endphp
                                    <tr class="{{ $isEvaluated ? 'table-success' : '' }}">
                                        <td>
                                            <span class="badge badge-secondary">Period {{ $entry->period_number }}</span>
                                        </td>
                                        <td>
                                            @if($entry->start_time && $entry->end_time)
                                                <small>
                                                    {{ Carbon\Carbon::parse($entry->start_time)->format('h:i A') }}
                                                    - 
                                                    {{ Carbon\Carbon::parse($entry->end_time)->format('h:i A') }}
                                                </small>
                                            @else
                                                <small class="text-muted">Time not set</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $entry->class->name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $entry->section->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            {{ $entry->subject->name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            @if($isEvaluated)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    Evaluated
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($isEvaluated)
                                                <a href="{{ route('teacher.institute.lesson-evaluation.create', ['school' => $school->id, 'routine_entry' => $entry->id]) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye mr-1"></i>
                                                    View/Update
                                                </a>
                                            @else
                                                <a href="{{ route('teacher.institute.lesson-evaluation.create', ['school' => $school->id, 'routine_entry' => $entry->id]) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-clipboard-check mr-1"></i>
                                                    Evaluate
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-chalkboard-teacher"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Classes</span>
                                        <span class="info-box-number">{{ $routineEntries->count() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Evaluated</span>
                                        <span class="info-box-number">{{ count($evaluatedIds) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Pending</span>
                                        <span class="info-box-number">{{ $routineEntries->count() - count($evaluatedIds) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
