@extends('layouts.admin')

@section('title', 'My Attendance Records')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">My Attendance Records</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('teacher.attendance.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-angle-left"></i> Back to Attendance
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">This Month's Attendance - {{ \Carbon\Carbon::now()->format('F Y') }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($attendances->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Total Time</th>
                                            <th>Status</th>
                                            <th>Photos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($attendances as $attendance)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d/m/Y (l)') }}</td>
                                                <td>
                                                    @if ($attendance->check_in_time)
                                                        <i class="fas fa-sign-in-alt text-success"></i>
                                                        {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->check_out_time)
                                                        <i class="fas fa-sign-out-alt text-warning"></i>
                                                        {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->check_in_time && $attendance->check_out_time)
                                                        @php
                                                            $checkIn = \Carbon\Carbon::parse($attendance->check_in_time);
                                                            $checkOut = \Carbon\Carbon::parse($attendance->check_out_time);
                                                            $diff = $checkIn->diff($checkOut);
                                                        @endphp
                                                        {{ $diff->h }}h {{ $diff->i }}m
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->status === 'present')
                                                        <span class="badge badge-success">Present</span>
                                                    @elseif ($attendance->status === 'late')
                                                        <span class="badge badge-warning">Late</span>
                                                    @elseif ($attendance->status === 'absent')
                                                        <span class="badge badge-danger">Absent</span>
                                                    @else
                                                        <span class="badge badge-info">Half Day</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->check_in_photo)
                                                        <button class="btn btn-xs btn-info view-photo" 
                                                                data-photo="{{ asset('storage/' . $attendance->check_in_photo) }}"
                                                                data-title="Check-in Photo">
                                                            <i class="fas fa-image"></i> Check-in
                                                        </button>
                                                    @endif
                                                    @if ($attendance->check_out_photo)
                                                        <button class="btn btn-xs btn-warning view-photo" 
                                                                data-photo="{{ asset('storage/' . $attendance->check_out_photo) }}"
                                                                data-title="Check-out Photo">
                                                            <i class="fas fa-image"></i> Check-out
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary -->
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="info-box bg-success">
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Present</span>
                                            <span class="info-box-number">{{ $attendances->where('status', 'present')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-warning">
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Late</span>
                                            <span class="info-box-number">{{ $attendances->where('status', 'late')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-danger">
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Absent</span>
                                            <span class="info-box-number">{{ $attendances->where('status', 'absent')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-info">
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Half Day</span>
                                            <span class="info-box-number">{{ $attendances->where('status', 'half_day')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No attendance records this month.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalTitle"></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="photoModalImage" src="" alt="Attendance Photo" class="img-fluid" style="max-height: 500px;">
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Wait for jQuery to be loaded
(function checkJQuery() {
    if (typeof jQuery === 'undefined') {
        setTimeout(checkJQuery, 50);
        return;
    }
    
    $(document).ready(function() {
        $('.view-photo').click(function() {
            const photo = $(this).data('photo');
            const title = $(this).data('title');
            
            $('#photoModalTitle').text(title);
            $('#photoModalImage').attr('src', photo);
            $('#photoModal').modal('show');
        });
    });
})();
</script>
@endsection
