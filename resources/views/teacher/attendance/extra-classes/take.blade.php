@extends('layouts.admin')

@section('title', 'Take Extra Class Attendance - ' . $extraClass->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Take Attendance</h1>
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
                        Subject: {{ $extraClass->subject->name ?? 'N/A' }}
                    </span>
                </div>
            </div>

            <form action="{{ route('teacher.institute.attendance.extra-classes.store', $school) }}" method="POST" id="attendanceForm" data-istoday="{{ $isToday ? '1' : '0' }}">
                @csrf
                <input type="hidden" name="extra_class_id" value="{{ $extraClass->id }}">
                <input type="hidden" name="date" value="{{ $date }}">

                <div class="card-body">
                    @if(session('success'))
                    @endif

                    @if(session('error'))
                    @endif
                    @isset($stats)
                        <div class="alert alert-info" style="margin-bottom: 15px;">
                            <strong>Status Summary:</strong>
                            <span class="badge badge-secondary" title="Total">মোট: {{ $stats['total'] }}</span>
                            <span class="badge badge-success" title="Present">উপস্থিত: {{ $stats['present'] }}</span>
                            <span class="badge badge-danger" title="Absent">অনুপস্থিত: {{ $stats['absent'] }}</span>
                            <span class="badge badge-warning" title="Late">দেরি: {{ $stats['late'] }}</span>
                            <span class="badge badge-primary" title="Excused">ছুটি: {{ $stats['excused'] }}</span>
                        </div>
                    @endisset

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date:</label>
                                <input type="date" class="form-control" value="{{ $date }}" max="{{ date('Y-m-d') }}" onchange="window.location.href='?extra_class_id={{ $extraClass->id }}&date='+this.value">
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <label class="d-block">&nbsp;</label>
                            @if($isToday)
                                <button type="button" class="btn btn-success btn-sm" id="markAllPresent">
                                    <i class="fas fa-check-double"></i> All Present
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" id="markAllAbsent">
                                    <i class="fas fa-times-circle"></i> All Absent
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th width="80">Roll</th>
                                        <th>Name</th>
                                        <th width="120">Section</th>
                                        <th width="300" class="text-center">Attendance</th>
                                        <th width="200">Remarks</th>
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
                                                    <label class="btn btn-outline-success {{ $status === 'present' ? 'active' : '' }} flex-fill {{ $isToday ? '' : 'disabled' }}">
                                                        <input type="radio" name="attendance[{{ $index }}][status]" value="present" {{ $status === 'present' ? 'checked' : '' }} {{ $isToday ? '' : 'disabled' }}>
                                                        <i class="fas fa-check"></i> Present
                                                    </label>
                                                    <label class="btn btn-outline-danger {{ $status === 'absent' ? 'active' : '' }} flex-fill {{ $isToday ? '' : 'disabled' }}">
                                                        <input type="radio" name="attendance[{{ $index }}][status]" value="absent" {{ $status === 'absent' ? 'checked' : '' }} {{ $isToday ? '' : 'disabled' }}>
                                                        <i class="fas fa-times"></i> Absent
                                                    </label>
                                                    <label class="btn btn-outline-warning {{ $status === 'late' ? 'active' : '' }} flex-fill {{ $isToday ? '' : 'disabled' }}">
                                                        <input type="radio" name="attendance[{{ $index }}][status]" value="late" {{ $status === 'late' ? 'checked' : '' }} {{ $isToday ? '' : 'disabled' }}>
                                                        <i class="fas fa-clock"></i> Late
                                                    </label>
                                                    <label class="btn btn-outline-info {{ $status === 'excused' ? 'active' : '' }} flex-fill {{ $isToday ? '' : 'disabled' }}">
                                                        <input type="radio" name="attendance[{{ $index }}][status]" value="excused" {{ $status === 'excused' ? 'checked' : '' }} {{ $isToday ? '' : 'disabled' }}>
                                                        <i class="fas fa-file-medical"></i> Excused
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="attendance[{{ $index }}][remarks]" 
                                                       value="{{ $remarks }}" 
                                                       placeholder="Remarks" {{ $isToday ? '' : 'disabled' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No enrolled students for this extra class.
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    @if($students->count() > 0)
                        @if($isToday)
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        @else
                            <span class="text-muted"><i class="fas fa-eye"></i> Viewing past records (read-only). Only today's attendance can be recorded.</span>
                        @endif
                    @endif
                    <a href="{{ route('teacher.institute.attendance.extra-classes.index', $school) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function(){
    function init(){
        const markAllPresent = document.getElementById('markAllPresent');
        const markAllAbsent = document.getElementById('markAllAbsent');
        const form = document.getElementById('attendanceForm');
        const isToday = form && form.dataset.istoday === '1';
        if (isToday && markAllPresent) markAllPresent.addEventListener('click', function(){
            document.querySelectorAll('input[type="radio"][value="present"]').forEach(r=>{ r.checked = true; });
            document.querySelectorAll('label.btn-outline-success').forEach(l=>l.classList.add('active'));
            document.querySelectorAll('label.btn-outline-danger, label.btn-outline-warning, label.btn-outline-info').forEach(l=>l.classList.remove('active'));
        });
        if (isToday && markAllAbsent) markAllAbsent.addEventListener('click', function(){
            document.querySelectorAll('input[type="radio"][value="absent"]').forEach(r=>{ r.checked = true; });
            document.querySelectorAll('label.btn-outline-danger').forEach(l=>l.classList.add('active'));
            document.querySelectorAll('label.btn-outline-success, label.btn-outline-warning, label.btn-outline-info').forEach(l=>l.classList.remove('active'));
        });

        if (isToday && form) form.addEventListener('submit', function(e){
            let allChecked = true;
            document.querySelectorAll('tbody tr').forEach(function(tr){
                if (!tr.querySelector('input[type="radio"]:checked')) allChecked = false;
            });
            if (!allChecked){ e.preventDefault(); alert('Mark attendance for all students!'); }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
@endpush
@endsection
