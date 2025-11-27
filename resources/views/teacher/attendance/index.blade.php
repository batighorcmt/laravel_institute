@extends('layouts.admin')

@section('title', 'My Attendance')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">My Attendance</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('teacher.attendance.my-attendance') }}" class="btn btn-outline-primary">
                    <i class="fas fa-history"></i> My Attendance Records
                </a>
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
                        <h3 class="card-title">Today's Attendance - {{ \Carbon\Carbon::today()->format('d/m/Y') }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($attendance)
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-check"></i> Attendance Info</h5>
                                <p class="mb-1"><strong>Check-in:</strong> {{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') : 'Not done yet' }}</p>
                                <p class="mb-1"><strong>Check-out:</strong> {{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') : 'Not done yet' }}</p>
                                <p class="mb-0"><strong>Status:</strong> 
                                    @if ($attendance->status === 'present')
                                        <span class="badge badge-success">Present</span>
                                    @elseif ($attendance->status === 'late')
                                        <span class="badge badge-warning">Late</span>
                                    @elseif ($attendance->status === 'absent')
                                        <span class="badge badge-danger">Absent</span>
                                    @else
                                        <span class="badge badge-info">Half Day</span>
                                    @endif
                                </p>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5>Check-in</h5>
                                        @if ($attendance && $attendance->check_in_time)
                                            <p class="text-success">✓ Done</p>
                                            <button class="btn btn-secondary" disabled>Check-in</button>
                                        @else
                                            <p class="text-muted">Not done yet</p>
                                            <button class="btn btn-primary btn-lg" id="checkInBtn">
                                                <i class="fas fa-sign-in-alt"></i> Do Check-in
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5>Check-out</h5>
                                        @if ($attendance && $attendance->check_out_time)
                                            <p class="text-success">✓ Done</p>
                                            <button class="btn btn-secondary" disabled>Check-out</button>
                                        @elseif ($attendance && $attendance->check_in_time)
                                            <p class="text-muted">Please check-out</p>
                                            <button class="btn btn-warning btn-lg" id="checkOutBtn">
                                                <i class="fas fa-sign-out-alt"></i> Do Check-out
                                            </button>
                                        @else
                                            <p class="text-muted">Please check-in first</p>
                                            <button class="btn btn-secondary" disabled>Check-out</button>
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
                                    <i class="fas fa-camera"></i> Capture Photo
                                </button>
                                <button class="btn btn-secondary" id="cancelBtn">Cancel</button>
                            </div>
                        </div>

                        @if ($settings)
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> 
                                    Check-in window: {{ \Carbon\Carbon::parse($settings->check_in_start)->format('h:i A') }} - {{ \Carbon\Carbon::parse($settings->check_in_end)->format('h:i A') }} 
                                    (Late after {{ \Carbon\Carbon::parse($settings->late_threshold)->format('h:i A') }})
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
                <h5 class="modal-title text-white"><i class="fas fa-check-circle"></i> Success!</h5>
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
                <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
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
            alert('Unable to access camera: ' + err.message);
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
                    alert('Unable to get location: ' + error.message);
                }
            );
        } else {
            alert('Your browser does not support geolocation.');
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
                $('#successTime').text('Time: ' + response.time);
                $('#successModal').modal('show');
                
                // Reload page after modal closes
                $('#successModal').on('hidden.bs.modal', function() {
                    location.reload();
                });
            },
            error: function(xhr) {
                stopCamera();
                alert('Error: ' + (xhr.responseJSON?.message || 'An error occurred'));
            }
        });
    }
    });
})();
</script>
@endsection
