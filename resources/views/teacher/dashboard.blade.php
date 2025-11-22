@extends('layouts.admin')
@section('title','শিক্ষক ড্যাশবোর্ড')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-chalkboard-teacher"></i> শিক্ষক ড্যাশবোর্ড</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Welcome Card -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>স্বাগতম, {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h3>
            </div>
            <div class="card-body">
                <p class="mb-3">আপনি সফলভাবে লগইন করেছেন। এই ড্যাশবোর্ড থেকে আপনি আপনার দৈনন্দিন কাজকর্ম পরিচালনা করতে পারবেন।</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info"><i class="fas fa-id-card"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">ইউজারনেম</span>
                                <span class="info-box-number">{{ auth()->user()->username ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success"><i class="fas fa-phone"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">মোবাইল</span>
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
                        <p>হাজিরা</p>
                    </div>
                    <a href="{{ route('teacher.attendance.index') }}" class="small-box-footer">
                        চেক-ইন/চেক-আউট <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><i class="fas fa-history"></i></h3>
                        <p>আমার হাজিরা</p>
                    </div>
                    <a href="{{ route('teacher.attendance.my-attendance') }}" class="small-box-footer">
                        বিস্তারিত দেখুন <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><i class="fas fa-book"></i></h3>
                        <p>ক্লাস ও বিষয়</p>
                    </div>
                    <a href="#" class="small-box-footer">
                        শীঘ্রই আসছে <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><i class="fas fa-tasks"></i></h3>
                        <p>আসন্ন কাজ</p>
                    </div>
                    <a href="#" class="small-box-footer">
                        শীঘ্রই আসছে <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Today's Info -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-gradient-primary">
                        <h3 class="card-title"><i class="far fa-calendar-alt mr-2"></i>আজকের তথ্য</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>তারিখ:</strong> {{ \Carbon\Carbon::now()->locale('bn')->translatedFormat('d F Y') }}</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>দিন:</strong> {{ \Carbon\Carbon::now()->locale('bn')->translatedFormat('l') }}</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>সময়:</strong> <span id="current-time"></span></p>
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
    const timeString = now.toLocaleTimeString('bn-BD', { 
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
