@extends('layouts.admin')

@section('title', 'Extra Class Attendance - ' . $school->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Extra Class Attendance</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item active">Extra Class Attendance</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Take Attendance -->
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-check"></i> Take Attendance</h3>
                    </div>
                    <form action="{{ route('principal.institute.extra-classes.attendance.take', $school) }}" method="GET">
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
                                <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-right"></i> Proceed
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daily Report -->
            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-alt"></i> Daily Report</h3>
                    </div>
                    <form action="{{ route('principal.institute.extra-classes.attendance.daily-report', $school) }}" method="GET">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="daily_extra_class_id">Select Extra Class <span class="text-danger">*</span></label>
                                <select class="form-control" id="daily_extra_class_id" name="extra_class_id" required>
                                    <option value="">-- Select Extra Class --</option>
                                    @foreach($extraClasses as $extraClass)
                                        <option value="{{ $extraClass->id }}">
                                            {{ $extraClass->name }} ({{ $extraClass->schoolClass->name }} - {{ $extraClass->section->name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="report_date">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="report_date" name="date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-alt"></i> View Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Monthly Report -->
            <div class="col-md-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Monthly Report</h3>
                    </div>
                    <form action="{{ route('principal.institute.extra-classes.attendance.monthly-report', $school) }}" method="GET">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="monthly_extra_class_id">Select Extra Class <span class="text-danger">*</span></label>
                                <select class="form-control" id="monthly_extra_class_id" name="extra_class_id" required>
                                    <option value="">-- Select Extra Class --</option>
                                    @foreach($extraClasses as $extraClass)
                                        <option value="{{ $extraClass->id }}">
                                            {{ $extraClass->name }} ({{ $extraClass->schoolClass->name }} - {{ $extraClass->section->name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="month">Month <span class="text-danger">*</span></label>
                                <input type="month" class="form-control" id="month" name="month" value="{{ date('Y-m') }}" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-calendar-alt"></i> View Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
