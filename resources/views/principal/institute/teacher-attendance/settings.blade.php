@extends('layouts.admin')

@section('title', 'Teacher Attendance Settings')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Teacher Attendance Settings</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item active">Teacher Attendance Settings</li>
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
                        <h3 class="card-title">Configure Attendance Times</h3>
                    </div>
                    <form action="{{ route('principal.institute.teacher-attendance.settings.store', $school) }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Configure the check-in and check-out times for teacher attendance. These settings will be used to determine if a teacher is present, late, or absent.
                            </div>

                            {{-- Check-in Settings --}}
                            <h5 class="mb-3"><i class="fas fa-sign-in-alt"></i> Check-In Settings</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="check_in_start">Check-In Starts At <span class="text-danger">*</span></label>
                                        <input type="time" 
                                               class="form-control @error('check_in_start') is-invalid @enderror" 
                                               id="check_in_start" 
                                               name="check_in_start" 
                                               value="{{ old('check_in_start', $settings->check_in_start ?? '08:00') }}" 
                                               required>
                                        <small class="text-muted">Teachers can start checking in from this time</small>
                                        @error('check_in_start')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="check_in_end">On-Time Check-In Deadline <span class="text-danger">*</span></label>
                                        <input type="time" 
                                               class="form-control @error('check_in_end') is-invalid @enderror" 
                                               id="check_in_end" 
                                               name="check_in_end" 
                                               value="{{ old('check_in_end', $settings->check_in_end ?? '09:00') }}" 
                                               required>
                                        <small class="text-muted">Check-in before this time = Present</small>
                                        @error('check_in_end')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="late_threshold">Late Threshold <span class="text-danger">*</span></label>
                                        <input type="time" 
                                               class="form-control @error('late_threshold') is-invalid @enderror" 
                                               id="late_threshold" 
                                               name="late_threshold" 
                                               value="{{ old('late_threshold', $settings->late_threshold ?? '09:30') }}" 
                                               required>
                                        <small class="text-muted">After this time = Late</small>
                                        @error('late_threshold')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <hr>

                            {{-- Check-out Settings --}}
                            <h5 class="mb-3"><i class="fas fa-sign-out-alt"></i> Check-Out Settings</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="check_out_start">Check-Out Starts At <span class="text-danger">*</span></label>
                                        <input type="time" 
                                               class="form-control @error('check_out_start') is-invalid @enderror" 
                                               id="check_out_start" 
                                               name="check_out_start" 
                                               value="{{ old('check_out_start', $settings->check_out_start ?? '14:00') }}" 
                                               required>
                                        <small class="text-muted">Teachers can start checking out from this time</small>
                                        @error('check_out_start')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="check_out_end">Normal Check-Out Time <span class="text-danger">*</span></label>
                                        <input type="time" 
                                               class="form-control @error('check_out_end') is-invalid @enderror" 
                                               id="check_out_end" 
                                               name="check_out_end" 
                                               value="{{ old('check_out_end', $settings->check_out_end ?? '17:00') }}" 
                                               required>
                                        <small class="text-muted">Expected check-out time</small>
                                        @error('check_out_end')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <hr>

                            {{-- Additional Settings --}}
                            <h5 class="mb-3"><i class="fas fa-cog"></i> Additional Settings</h5>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="require_photo" 
                                           name="require_photo" 
                                           value="1"
                                           {{ old('require_photo', $settings->require_photo ?? true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="require_photo">
                                        Require Photo Capture
                                        <small class="text-muted d-block">Teachers must take a photo when checking in/out</small>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="require_location" 
                                           name="require_location" 
                                           value="1"
                                           {{ old('require_location', $settings->require_location ?? true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="require_location">
                                        Require Location Capture
                                        <small class="text-muted d-block">Teachers' location will be recorded when checking in/out</small>
                                    </label>
                                </div>
                            </div>

                            {{-- Example Timeline --}}
                            <div class="alert alert-light mt-4">
                                <h6><i class="fas fa-clock"></i> Example Timeline</h6>
                                <ul class="mb-0">
                                    <li>Check-in starts: <strong><span id="preview_check_in_start">08:00 AM</span></strong></li>
                                    <li>On-time deadline: <strong><span id="preview_check_in_end">09:00 AM</span></strong> (Status: Present)</li>
                                    <li>Late after: <strong><span id="preview_late_threshold">09:30 AM</span></strong> (Status: Late)</li>
                                    <li>Check-out starts: <strong><span id="preview_check_out_start">02:00 PM</span></strong></li>
                                    <li>Expected check-out: <strong><span id="preview_check_out_end">05:00 PM</span></strong></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                            <a href="{{ route('principal.institute.manage', $school) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Update preview when times change
    function updatePreview() {
        const checkInStart = $('#check_in_start').val();
        const checkInEnd = $('#check_in_end').val();
        const lateThreshold = $('#late_threshold').val();
        const checkOutStart = $('#check_out_start').val();
        const checkOutEnd = $('#check_out_end').val();

        $('#preview_check_in_start').text(formatTime(checkInStart));
        $('#preview_check_in_end').text(formatTime(checkInEnd));
        $('#preview_late_threshold').text(formatTime(lateThreshold));
        $('#preview_check_out_start').text(formatTime(checkOutStart));
        $('#preview_check_out_end').text(formatTime(checkOutEnd));
    }

    function formatTime(time) {
        if (!time) return '';
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }

    $('input[type="time"]').on('change', updatePreview);
    updatePreview();
});
</script>
@endsection
