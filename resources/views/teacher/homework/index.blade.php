@extends('layouts.admin')

@section('title', 'Homework')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="m-0">Homework</h1>
    <div>
        <a href="{{ route('teacher.institute.homework.create', $school) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Homework
        </a>
        <a href="{{ route('teacher.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<!-- Search Form -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('teacher.institute.homework.index', $school) }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Homeworks List -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">
            <i class="fas fa-book"></i> Homework List ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})
        </h3>
    </div>
    <div class="card-body">
        @if($homeworks->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Class/Section</th>
                            <th>Subject</th>
                            <th>Title</th>
                            <th>Submission Date</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($homeworks as $homework)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $homework->schoolClass->name }} - {{ $homework->section->name }}</td>
                                <td>{{ $homework->subject->name }}</td>
                                <td>{{ $homework->title }}</td>
                                <td>{{ $homework->submission_date ? $homework->submission_date->format('d/m/Y') : 'Not set' }}</td>
                                <td>
                                    <a href="{{ route('teacher.institute.homework.show', [$school, $homework]) }}" class="btn btn-sm btn-info" title="Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('teacher.institute.homework.destroy', [$school, $homework]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> No homework on this date.
            </div>
        @endif
    </div>
</div>

<style>
.card-header.bg-primary {
    background: linear-gradient(45deg, #4e73df, #224abe) !important;
}
</style>
@endsection
