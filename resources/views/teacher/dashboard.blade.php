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

        <!-- Quick Links -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><i class="fas fa-calendar-check"></i></h3>
                        <p>Attendance</p>
                    </div>
                    <a href="{{ route('teacher.attendance.index') }}" class="small-box-footer">
                        Check-in/Check-out <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><i class="fas fa-history"></i></h3>
                        <p>My Attendance</p>
                    </div>
                    <a href="{{ route('teacher.attendance.my-attendance') }}" class="small-box-footer">
                        View details <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><i class="fas fa-book"></i></h3>
                        <p>Classes & Subjects</p>
                    </div>
                    <a href="#" class="small-box-footer">
                        Coming soon <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><i class="fas fa-tasks"></i></h3>
                        <p>Upcoming Tasks</p>
                    </div>
                    <a href="#" class="small-box-footer">
                        Coming soon <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Today's Info -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-gradient-primary">
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
