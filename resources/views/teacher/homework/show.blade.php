@extends('layouts.admin')

@section('title', 'Homework Details')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="m-0">Homework Details</h1>
    <a href="{{ route('teacher.institute.homework.index', $school) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">
            <i class="fas fa-book"></i> {{ $homework->title }}
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Class</th>
                        <td>{{ $homework->schoolClass->name }}</td>
                    </tr>
                    <tr>
                        <th>Section</th>
                        <td>{{ $homework->section->name }}</td>
                    </tr>
                    <tr>
                        <th>Subject</th>
                        <td>{{ $homework->subject->name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Assigned Date</th>
                        <td>{{ $homework->homework_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Submission Date</th>
                        <td>{{ $homework->submission_date ? $homework->submission_date->format('d/m/Y') : 'নির্ধারিত নয়' }}</td>
                    </tr>
                    <tr>
                        <th>Teacher</th>
                        <td>{{ $homework->teacher->first_name_bn ?? $homework->teacher->first_name }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <h4>Details:</h4>
            <div class="border p-3 bg-light">
                {!! nl2br(e($homework->description)) !!}
            </div>
        </div>

        @if($homework->attachment)
            <div class="mt-4">
                <h4>Attachment:</h4>
                <a href="{{ asset('storage/' . $homework->attachment) }}" target="_blank" class="btn btn-info">
                    <i class="fas fa-download"></i> Download File
                </a>
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
