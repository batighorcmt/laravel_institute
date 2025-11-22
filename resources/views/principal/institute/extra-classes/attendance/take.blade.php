@extends('layouts.admin')

@section('title', 'Take Attendance - ' . $extraClass->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Take Attendance</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $school) }}">Extra Class Attendance</a></li>
                    <li class="breadcrumb-item active">Take Attendance</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">{{ $extraClass->name }}</h3>
                <div class="card-tools">
                    <span class="badge badge-light">
                        Class: {{ $extraClass->schoolClass->name ?? 'N/A' }} | 
                        Subject: {{ $extraClass->subject->name ?? 'N/A' }} |
                        Teacher: {{ $extraClass->teacher->name ?? 'N/A' }}
                    </span>
                </div>
            </div>

            <form action="{{ route('principal.institute.extra-classes.attendance.store', $school) }}" method="POST" id="attendanceForm">
                @csrf
                <input type="hidden" name="extra_class_id" value="{{ $extraClass->id }}">
                <input type="hidden" name="date" value="{{ $date }}">

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date:</label>
                                <input type="date" class="form-control" value="{{ $date }}" onchange="window.location.href='?extra_class_id={{ $extraClass->id }}&date='+this.value">
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <label class="d-block">&nbsp;</label>
                            <button type="button" class="btn btn-success btn-sm" id="markAllPresent">
                                <i class="fas fa-check-double"></i> All Present
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" id="markAllAbsent">
                                <i class="fas fa-times-circle"></i> All Absent
                            </button>
                        </div>
                    </div>

                    @if($students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="50">ক্রমিক</th>
                                        <th width="80">রোল</th>
                                        <th>নাম</th>
                                        <th width="120">সেকশন</th>
                                        <th width="300" class="text-center">উপস্থিতি</th>
                                        <th width="200">মন্তব্য</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $index => $student)
                                        @php
                                            $existingRecord = $attendanceRecords->get($student->id);
                                            $status = $existingRecord ? $existingRecord->status : 'present';
                                            $remarks = $existingRecord ? $existingRecord->remarks : '';
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td class="text-center"><strong>{{ $student->roll_no }}</strong></td>
                                            <td>{{ $student->name }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-secondary">{{ $student->section_name }}</span>
                                            </td>
                                            <td>
                                                <input type="hidden" name="attendance[{{ $index }}][student_id]" value="{{ $student->id }}">
                                                <div class="btn-group btn-group-sm btn-group-toggle d-flex" data-toggle="buttons">
                                                    <label class="btn btn-outline-success {{ $status === 'present' ? 'active' : '' }} flex-fill">
                                                        <input type="radio" name="attendance[{{ $index }}][status]" value="present" {{ $status === 'present' ? 'checked' : '' }}>
                                                        <i class="fas fa-check"></i> উপস্থিত
                                                    </label>
                                                    <label class="btn btn-outline-danger {{ $status === 'absent' ? 'active' : '' }} flex-fill">
                                                        <input type="radio" name="attendance[{{ $index }}][status]" value="absent" {{ $status === 'absent' ? 'checked' : '' }}>
                                                        <i class="fas fa-times"></i> অনুপস্থিত
                                                    </label>
                                                    <label class="btn btn-outline-warning {{ $status === 'late' ? 'active' : '' }} flex-fill">
                                                        <input type="radio" name="attendance[{{ $index }}][status]" value="late" {{ $status === 'late' ? 'checked' : '' }}>
                                                        <i class="fas fa-clock"></i> বিলম্ব
                                                    </label>
                                                    <label class="btn btn-outline-info {{ $status === 'excused' ? 'active' : '' }} flex-fill">
                                                        <input type="radio" name="attendance[{{ $index }}][status]" value="excused" {{ $status === 'excused' ? 'checked' : '' }}>
                                                        <i class="fas fa-file-medical"></i> ছুটি
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="attendance[{{ $index }}][remarks]" 
                                                       value="{{ $remarks }}" 
                                                       placeholder="মন্তব্য">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            এই এক্সট্রা ক্লাসে কোনো শিক্ষার্থী নথিভুক্ত নেই।
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    @if($students->count() > 0)
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> হাজিরা সংরক্ষণ করুন
                        </button>
                    @endif
                    <a href="{{ route('principal.institute.extra-classes.attendance.index', $school) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> ফিরে যান
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    // Mark all present
    $('#markAllPresent').click(function() {
        $('input[type="radio"][value="present"]').prop('checked', true);
        $('label.btn-outline-success').addClass('active');
        $('label.btn-outline-danger, label.btn-outline-warning, label.btn-outline-info').removeClass('active');
    });

    // Mark all absent
    $('#markAllAbsent').click(function() {
        $('input[type="radio"][value="absent"]').prop('checked', true);
        $('label.btn-outline-danger').addClass('active');
        $('label.btn-outline-success, label.btn-outline-warning, label.btn-outline-info').removeClass('active');
    });

    // Form validation
    $('#attendanceForm').submit(function(e) {
        let allChecked = true;
        $('tbody tr').each(function() {
            if (!$(this).find('input[type="radio"]:checked').length) {
                allChecked = false;
            }
        });
        
        if (!allChecked) {
            e.preventDefault();
            alert('সকল শিক্ষার্থীর হাজিরা চিহ্নিত করুন!');
            return false;
        }
    });
});
</script>
@endpush
@endsection
