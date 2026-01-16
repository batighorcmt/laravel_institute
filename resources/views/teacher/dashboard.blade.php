@extends('layouts.admin')
@section('title','Teacher Dashboard')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Welcome Card -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>Welcome, {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h3>
            </div>
            <div class="card-body">
                <p class="mb-3">You are logged in. Use this dashboard to manage your daily tasks.</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info"><i class="fas fa-id-card"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Username</span>
                                <span class="info-box-number">{{ auth()->user()->username ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success"><i class="fas fa-phone"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Mobile</span>
                                <span class="info-box-number">{{ auth()->user()->phone ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Classes -->
        @if($assignedClasses->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-gradient-info">
                        <h3 class="card-title"><i class="fas fa-school mr-2"></i>My Assigned Classes</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($assignedClasses as $class)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h4>{{ $class->name }}</h4>
                                        <p>Class Teacher</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-chalkboard"></i>
                                    </div>
                                    <a href="{{ route('teacher.institute.directory.students', $school) }}?class_id={{ $class->id }}" class="small-box-footer">
                                        View Students <i class="fas fa-arrow-circle-right"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Today's Routine -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-gradient-success">
                        <h3 class="card-title"><i class="fas fa-calendar-day mr-2"></i>Today's Routine ({{ \Carbon\Carbon::now()->format('l') }})</h3>
                    </div>
                    <div class="card-body">
                        @if($todayRoutine->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Class</th>
                                        <th>Subject</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todayRoutine as $entry)
                                    <tr>
                                        <td>{{ $entry->start_time }} - {{ $entry->end_time }}</td>
                                        <td>{{ $entry->class->name ?? 'N/A' }}</td>
                                        <td>{{ $entry->subject->name ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-muted">No classes scheduled for today.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Tasks -->
        <div class="row">
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-header bg-gradient-warning">
                        <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Pending Tasks</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-danger"><i class="fas fa-clipboard-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Evaluations Pending</span>
                                        <span class="info-box-number">{{ $pendingEvaluations }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-header bg-gradient-primary">
                        <h3 class="card-title"><i class="fas fa-link mr-2"></i>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <a href="{{ route('teacher.attendance.index') }}" class="btn btn-info btn-block">
                                    <i class="fas fa-calendar-check"></i><br>Attendance
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="{{ route('teacher.attendance.my-attendance') }}" class="btn btn-success btn-block">
                                    <i class="fas fa-history"></i><br>My Attendance
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="{{ route('teacher.institute.homework.index', $school) }}" class="btn btn-warning btn-block">
                                    <i class="fas fa-book"></i><br>Homework
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="{{ route('teacher.institute.lesson-evaluation.index', $school) }}" class="btn btn-danger btn-block">
                                    <i class="fas fa-clipboard-check"></i><br>Evaluations
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Info -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-gradient-secondary">
                        <h3 class="card-title"><i class="far fa-calendar-alt mr-2"></i>Today's Info</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d F Y') }}</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Day:</strong> {{ \Carbon\Carbon::now()->format('l') }}</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Time:</strong> <span id="current-time"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
    document.getElementById('current-time').textContent = timeString;
}
updateTime();
setInterval(updateTime, 1000);
</script>
@endpush

@endsection
