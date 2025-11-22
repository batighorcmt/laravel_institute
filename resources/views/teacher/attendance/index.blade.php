@extends('layouts.admin')

@section('title', 'আমার হাজিরা')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">আমার হাজিরা</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    @if(auth()->user()->isPrincipal())
                        <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">ড্যাশবোর্ড</a></li>
                    @endif
                    <li class="breadcrumb-item active">আমার হাজিরা</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">আজকের হাজিরা - {{ \Carbon\Carbon::today()->format('d/m/Y') }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($attendance)
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-check"></i> হাজিরার তথ্য</h5>
                                <p class="mb-1"><strong>চেক-ইন:</strong> {{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') : 'এখনো করা হয়নি' }}</p>
                                <p class="mb-1"><strong>চেক-আউট:</strong> {{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') : 'এখনো করা হয়নি' }}</p>
                                <p class="mb-0"><strong>স্ট্যাটাস:</strong> 
                                    @if ($attendance->status === 'present')
                                        <span class="badge badge-success">উপস্থিত</span>
                                    @elseif ($attendance->status === 'late')
                                        <span class="badge badge-warning">বিলম্ব</span>
                                    @elseif ($attendance->status === 'absent')
                                        <span class="badge badge-danger">অনুপস্থিত</span>
                                    @else
                                        <span class="badge badge-info">হাফ ডে</span>
                                    @endif
                                </p>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5>চেক-ইন</h5>
                                        @if ($attendance && $attendance->check_in_time)
                                            <p class="text-success">✓ সম্পন্ন হয়েছে</p>
                                            <button class="btn btn-secondary" disabled>চেক-ইন</button>
                                        @else
                                            <p class="text-muted">এখনো করা হয়নি</p>
                                            <button class="btn btn-primary btn-lg" id="checkInBtn">
                                                <i class="fas fa-sign-in-alt"></i> চেক-ইন করুন
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5>চেক-আউট</h5>
                                        @if ($attendance && $attendance->check_out_time)
                                            <p class="text-success">✓ সম্পন্ন হয়েছে</p>
                                            <button class="btn btn-secondary" disabled>চেক-আউট</button>
                                        @elseif ($attendance && $attendance->check_in_time)
                                            <p class="text-muted">চেক-আউট করুন</p>
                                            <button class="btn btn-warning btn-lg" id="checkOutBtn">
                                                <i class="fas fa-sign-out-alt"></i> চেক-আউট করুন
                                            </button>
                                        @else
                                            <p class="text-muted">প্রথমে চেক-ইন করুন</p>
                                            <button class="btn btn-secondary" disabled>চেক-আউট</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Camera Preview (Hidden by default) -->
                        <div id="cameraSection" style="display: none;">
                            <div class="text-center mb-3">
                                <video id="video" width="400" height="300" autoplay style="border: 2px solid #ccc; border-radius: 8px;"></video>
                                <canvas id="canvas" width="400" height="300" style="display: none;"></canvas>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-success btn-lg" id="captureBtn">
                                    <i class="fas fa-camera"></i> ছবি তুলুন
                                </button>
                                <button class="btn btn-secondary" id="cancelBtn">বাতিল</button>
                            </div>
                        </div>

                        @if ($settings)
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> 
                                    চেক-ইন সময়: {{ \Carbon\Carbon::parse($settings->check_in_start)->format('h:i A') }} - {{ \Carbon\Carbon::parse($settings->check_in_end)->format('h:i A') }} 
                                    (বিলম্ব: {{ \Carbon\Carbon::parse($settings->late_threshold)->format('h:i A') }} এর পরে)
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white"><i class="fas fa-check-circle"></i> সফল!</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 64px;"></i>
                <h4 class="mt-3" id="successMessage"></h4>
                <p id="successTime"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal">ধন্যবাদ</button>
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
        let stream = null;
        let currentAction = null; // 'checkin' or 'checkout'

        $('#checkInBtn').click(function() {
            currentAction = 'checkin';
            startCamera();
        });

        $('#checkOutBtn').click(function() {
            currentAction = 'checkout';
            startCamera();
        });

        $('#cancelBtn').click(function() {
            stopCamera();
        });

        $('#captureBtn').click(function() {
            capturePhoto();
        });

        function startCamera() {
            $('#cameraSection').show();
        
        // Request camera access (front camera)
        navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user', // Front camera
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        })
        .then(function(mediaStream) {
            stream = mediaStream;
            const video = document.getElementById('video');
            video.srcObject = stream;
        })
        .catch(function(err) {
            alert('ক্যামেরা অ্যাক্সেস করা যায়নি: ' + err.message);
            $('#cameraSection').hide();
        });
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        $('#cameraSection').hide();
        currentAction = null;
    }

    function capturePhoto() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        
        // Draw video frame to canvas
        context.drawImage(video, 0, 0, 400, 300);
        
        // Convert to low-resolution base64
        const photoData = canvas.toDataURL('image/png', 0.5); // 50% quality
        
        // Get geolocation
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    submitAttendance(photoData, position.coords.latitude, position.coords.longitude);
                },
                function(error) {
                    alert('লোকেশন পাওয়া যায়নি: ' + error.message);
                }
            );
        } else {
            alert('আপনার ব্রাউজার জিওলোকেশন সাপোর্ট করে না।');
        }
    }

    function submitAttendance(photo, latitude, longitude) {
        const url = currentAction === 'checkin' 
            ? '{{ route("teacher.attendance.check-in") }}' 
            : '{{ route("teacher.attendance.check-out") }}';
        
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                photo: photo,
                latitude: latitude,
                longitude: longitude
            },
            success: function(response) {
                stopCamera();
                $('#successMessage').text(response.message);
                $('#successTime').text('সময়: ' + response.time);
                $('#successModal').modal('show');
                
                // Reload page after modal closes
                $('#successModal').on('hidden.bs.modal', function() {
                    location.reload();
                });
            },
            error: function(xhr) {
                stopCamera();
                alert('Error: ' + (xhr.responseJSON?.message || 'একটি ত্রুটি ঘটেছে'));
            }
        });
    }
    });
})();
</script>
@endsection
