@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Room Attendance</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Room Attendance</li>
                </ol>
            </nav>
        </div>

        @if($planId && $roomId)
        <div class="card shadow-none border-0 mb-3 bg-transparent">
            <div class="card-body py-1 px-0">
                <div class="d-flex flex-wrap gap-2">
                    <div class="info-chip bg-secondary rounded shadow-sm">
                        <span class="label">Total</span>
                        <span class="value" id="stat-total">{{ $stats['total'] }}</span>
                    </div>
                    <div class="info-chip bg-info rounded shadow-sm">
                        <span class="label">Male</span>
                        <span class="value" id="stat-male">{{ $stats['male'] }}</span>
                    </div>
                    <div class="info-chip bg-warning text-dark rounded shadow-sm">
                        <span class="label">Female</span>
                        <span class="value" id="stat-female">{{ $stats['female'] }}</span>
                    </div>
                    <div class="info-chip bg-success rounded shadow-sm">
                        <span class="label">Present</span>
                        <span class="value" id="stat-present">{{ $stats['present'] }}</span>
                    </div>
                    <div class="info-chip bg-danger rounded shadow-sm">
                        <span class="label">Absent</span>
                        <span class="value" id="stat-absent">{{ $stats['absent'] }}</span>
                    </div>
                </div>
                
                @if(!empty($classCounts))
                <div class="mt-2 d-flex flex-wrap gap-2">
                    @foreach($classCounts as $className => $count)
                        <span class="badge badge-light border text-sm py-2 px-3 shadow-none">
                            {{ $className }}: <strong>{{ $count }}</strong>
                        </span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form id="filterForm" method="GET" action="{{ url()->current() }}">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label>Seat Plan</label>
                            <select name="plan_id" class="form-control select2" onchange="this.form.submit()">
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}" {{ $p->id == $planId ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->shift }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Date</label>
                            @if($isPrincipal)
                                <select name="date" class="form-control select2" onchange="this.form.submit()">
                                    @foreach($examDates as $d)
                                        <option value="{{ $d }}" {{ $d == $date ? 'selected' : '' }}>
                                            {{ date('d-m-Y', strtotime($d)) }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ date('d-m-Y', strtotime($date)) }}" readonly>
                                <input type="hidden" name="date" value="{{ $date }}">
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label>Room</label>
                            <select name="room_id" class="form-control select2" onchange="this.form.submit()">
                                @foreach($rooms as $r)
                                    <option value="{{ $r->id }}" {{ $r->id == $roomId ? 'selected' : '' }}>
                                        {{ $r->room_no }} @if($r->title) — {{ $r->title }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($planId && $roomId)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-user-check mr-1"></i>
                    Attendance — {{ date('d-m-Y', strtotime($date)) }} | Room #{{ collect($rooms)->firstWhere('id', $roomId)->room_no ?? $roomId }}
                </h3>
                <div class="card-tools">
                    @php
                        $allPresent = count($students) > 0 && collect($students)->every(fn($s) => $s['status'] === 'present');
                    @endphp
                    <button type="button" class="btn btn-sm {{ $allPresent ? 'btn-danger' : 'btn-success' }}" id="btnBulkMark" data-mode="{{ $allPresent ? 'absent' : 'present' }}">
                        <i class="fas {{ $allPresent ? 'fa-times-circle' : 'fa-check-circle' }} mr-1"></i>
                        <span class="btn-text">{{ $allPresent ? 'Mark all absent' : 'Mark all present' }}</span>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="10%">Roll</th>
                                <th>Name</th>
                                <th width="20%">Class</th>
                                <th width="20%" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $s)
                            <tr data-student-id="{{ $s['id'] }}">
                                <td class="font-weight-bold">{{ $s['roll'] }}</td>
                                <td>{{ $s['name'] }}</td>
                                <td>{{ $s['class'] }}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                        <label class="btn btn-sm {{ $s['status'] === 'present' ? 'btn-success active' : 'btn-outline-success' }} js-mark" data-status="present">
                                            <input type="radio" autocomplete="off" {{ $s['status'] === 'present' ? 'checked' : '' }}> Present
                                        </label>
                                        <label class="btn btn-sm {{ $s['status'] === 'absent' ? 'btn-danger active' : 'btn-outline-danger' }} js-mark" data-status="absent">
                                            <input type="radio" autocomplete="off" {{ $s['status'] === 'absent' ? 'checked' : '' }}> Absent
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No students found for this room/date.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-info shadow-sm">
            Please select a seat plan, date, and room to view the student list.
        </div>
        @endif
    </div>
</div>

<style>
    .info-chip {
        display: inline-flex;
        align-items: baseline;
        gap: 0.5rem;
        padding: 0.5rem 0.8rem;
        border-radius: 8px;
        color: #fff;
        font-weight: 600;
        min-width: 100px;
    }
    .info-chip .label { font-size: 0.7rem; opacity: 0.85; text-transform: uppercase; }
    .info-chip .value { font-size: 1.1rem; }
    .gap-2 { gap: 0.5rem; }
    
    /* Ensure card headers are flexbox */
    .card-header { display: flex; align-items: center; justify-content: space-between; }
    .card-tools { display: flex; align-items: center; }
    /* Full width adjustment */
    .content-wrapper > .content > .container-fluid {
        max-width: 100% !important;
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
</style>

@endsection

@section('scripts')
<script>
window.addEventListener('load', function() {
    (function($) {
        if (!$) {
            console.error('jQuery not found even after window load');
            return;
        }

        console.log('Room Attendance JS initializing...');

        function recalcStats() {
            let present = 0, absent = 0;
            $('.js-mark.active').each(function() {
                if ($(this).data('status') === 'present') present++;
                else absent++;
            });
            $('#stat-present').text(present);
            $('#stat-absent').text(absent);
        }

        $(document).on('click', '.js-mark', function() {
            const btn = $(this);
            const tr = btn.closest('tr');
            const studentId = tr.data('student-id');
            const status = btn.data('status');
            const group = btn.closest('.btn-group');

            // UI update
            group.find('.js-mark').removeClass('active btn-success btn-danger')
                 .addClass(status === 'present' ? 'btn-outline-success' : 'btn-outline-danger');
            btn.addClass('active ' + (status === 'present' ? 'btn-success' : 'btn-danger'))
               .removeClass('btn-outline-success btn-outline-danger');

            // AJAX save
            $.ajax({
                url: "{{ route(request()->routeIs('teacher.*') ? 'teacher.institute.exams.room-attendance.mark' : 'principal.institute.exams.room-attendance.mark', $school) }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    date: '{{ $date }}',
                    plan_id: '{{ $planId }}',
                    room_id: '{{ $roomId }}',
                    student_id: studentId,
                    status: status
                },
                success: function(res) {
                    if(res.success) {
                        toastr.success('Saved successfully');
                        recalcStats();
                    } else {
                        toastr.error(res.message || 'Save failed');
                        location.reload(); 
                    }
                },
                error: function() {
                    toastr.error('Connection error');
                    location.reload();
                }
            });
        });

        $('#btnBulkMark').on('click', function() {
            const btn = $(this);
            const mode = btn.data('mode');
            bulkMark(mode);
        });

        function bulkMark(mode) {
            if (!confirm('Are you sure you want to mark all as ' + mode + '?')) return;
            
            const btn = $('#btnBulkMark');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');

            $.ajax({
                url: "{{ route(request()->routeIs('teacher.*') ? 'teacher.institute.exams.room-attendance.mark-all' : 'principal.institute.exams.room-attendance.mark-all', $school) }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    date: '{{ $date }}',
                    plan_id: '{{ $planId }}',
                    room_id: '{{ $roomId }}',
                    mode: mode
                },
                success: function(res) {
                    if(res.success) {
                        location.reload();
                    } else {
                        toastr.error('Bulk update failed');
                        btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function() {
                    toastr.error('Connection error');
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        }

        // Initialize Select2 if present
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%'
            });
        }
    })(window.jQuery || window.$);
});
</script>
@endsection
