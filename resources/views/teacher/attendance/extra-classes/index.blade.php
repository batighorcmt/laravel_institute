@extends('layouts.admin')

@section('title', 'Extra Class Attendance')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Extra Class Attendance</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Take Attendance</h3>
            </div>
            <form action="{{ route('teacher.institute.attendance.extra-classes.take', $school) }}" method="GET">
                <div class="card-body">
                    <div class="form-group">
                        <label for="extra_class_id">Select Extra Class <span class="text-danger">*</span></label>
                        <select class="form-control" id="extra_class_id" name="extra_class_id" required>
                            <option value="">-- Select Extra Class --</option>
                            @foreach($extraClasses as $extraClass)
                                <option value="{{ $extraClass->id }}">
                                    {{ $extraClass->name }} ({{ $extraClass->schoolClass->name }} - {{ $extraClass->section->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Proceed</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
