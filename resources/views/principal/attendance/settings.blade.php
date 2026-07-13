@extends('layouts.admin')

@section('title', 'হাজিরা সেটিংস')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="m-0"><i class="fas fa-clock mr-1 text-primary"></i> হাজিরা সেটিংস</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 bg-transparent p-0">
            <li class="breadcrumb-item"><a href="{{ route('principal.institute.attendance.dashboard', $school) }}">হাজিরা</a></li>
            <li class="breadcrumb-item active">সেটিংস</li>
        </ol>
    </nav>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif

<form method="POST" action="{{ route('principal.institute.attendance.settings.store', $school) }}">
    @csrf

    <div class="row">
        {{-- Student Settings --}}
        <div class="col-md-6">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-graduate mr-1"></i> শিক্ষার্থী হাজিরার নিয়ম</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>হাজিরা গ্রহণ শুরু (Entry Start)</label>
                        <input type="time" name="student_entry_start" class="form-control @error('student_entry_start') is-invalid @enderror"
                               value="{{ old('student_entry_start', \Carbon\Carbon::parse($settings->student_entry_start ?? '07:00:00')->format('H:i')) }}">
                        <small class="text-muted">এই সময়ের আগে কোনো পাঞ্চ হাজিরায় গণনা হবে না।</small>
                        @error('student_entry_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>সময়মত হাজিরার শেষ সময় (On-Time Entry End)</label>
                        <input type="time" name="student_entry_end" class="form-control @error('student_entry_end') is-invalid @enderror"
                               value="{{ old('student_entry_end', \Carbon\Carbon::parse($settings->student_entry_end ?? '08:45:00')->format('H:i')) }}">
                        <small class="text-muted">এই সময়ের আগে পাঞ্চ করলে <strong>উপস্থিত</strong> গণনা হবে।</small>
                        @error('student_entry_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>দেরীর শেষ সীমা (Late Threshold)</label>
                        <input type="time" name="student_late_threshold" class="form-control @error('student_late_threshold') is-invalid @enderror"
                               value="{{ old('student_late_threshold', \Carbon\Carbon::parse($settings->student_late_threshold ?? '09:30:00')->format('H:i')) }}">
                        <small class="text-muted">Entry End থেকে এই সময়ের মধ্যে পাঞ্চ করলে <strong>দেরী</strong>, এর পরে <strong>অনুপস্থিত</strong> গণনা হবে।</small>
                        @error('student_late_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>বাহির হাজিরা শুরু (Exit Start)</label>
                        <input type="time" name="student_exit_start" class="form-control @error('student_exit_start') is-invalid @enderror"
                               value="{{ old('student_exit_start', \Carbon\Carbon::parse($settings->student_exit_start ?? '13:00:00')->format('H:i')) }}">
                        <small class="text-muted">এই সময়ের পর থেকে পাঞ্চকে <strong>এক্সিট</strong> হিসেবে গণনা করা হবে।</small>
                        @error('student_exit_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>বাহির হাজিরার শেষ সময় (Exit End)</label>
                        <input type="time" name="student_exit_end" class="form-control @error('student_exit_end') is-invalid @enderror"
                               value="{{ old('student_exit_end', \Carbon\Carbon::parse($settings->student_exit_end ?? '15:00:00')->format('H:i')) }}">
                        <small class="text-muted">এই সময়ের পরে বাহির হাজিরা নেওয়া বন্ধ থাকবে।</small>
                        @error('student_exit_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Teacher Settings --}}
        <div class="col-md-6">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chalkboard-teacher mr-1"></i> শিক্ষক হাজিরার নিয়ম</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>চেক-ইন শুরু (Check-in Start)</label>
                        <input type="time" name="teacher_check_in_start" class="form-control @error('teacher_check_in_start') is-invalid @enderror"
                               value="{{ old('teacher_check_in_start', \Carbon\Carbon::parse($settings->teacher_check_in_start ?? '08:00:00')->format('H:i')) }}">
                        <small class="text-muted">এই সময়ের আগে পাঞ্চ গ্রহণযোগ্য নয়।</small>
                        @error('teacher_check_in_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>সময়মত চেক-ইনের শেষ (On-Time Check-in End)</label>
                        <input type="time" name="teacher_check_in_end" class="form-control @error('teacher_check_in_end') is-invalid @enderror"
                               value="{{ old('teacher_check_in_end', \Carbon\Carbon::parse($settings->teacher_check_in_end ?? '09:00:00')->format('H:i')) }}">
                        <small class="text-muted">এই সময়ের আগে পাঞ্চ করলে <strong>উপস্থিত</strong> গণনা হবে।</small>
                        @error('teacher_check_in_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>দেরীর শেষ সীমা (Late Threshold)</label>
                        <input type="time" name="teacher_late_threshold" class="form-control @error('teacher_late_threshold') is-invalid @enderror"
                               value="{{ old('teacher_late_threshold', \Carbon\Carbon::parse($settings->teacher_late_threshold ?? '09:30:00')->format('H:i')) }}">
                        <small class="text-muted">Check-in End থেকে এই সময়ের মধ্যে পাঞ্চ করলে <strong>দেরী</strong>।</small>
                        @error('teacher_late_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>চেক-আউট শুরু (Check-out Start)</label>
                        <input type="time" name="teacher_check_out_start" class="form-control @error('teacher_check_out_start') is-invalid @enderror"
                               value="{{ old('teacher_check_out_start', \Carbon\Carbon::parse($settings->teacher_check_out_start ?? '14:00:00')->format('H:i')) }}">
                        <small class="text-muted">এই সময়ের পর থেকে পাঞ্চকে <strong>চেক-আউট</strong> হিসেবে গণনা করা হবে।</small>
                        @error('teacher_check_out_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>চেক-আউটের শেষ সময় (Check-out End)</label>
                        <input type="time" name="teacher_check_out_end" class="form-control @error('teacher_check_out_end') is-invalid @enderror"
                               value="{{ old('teacher_check_out_end', \Carbon\Carbon::parse($settings->teacher_check_out_end ?? '17:00:00')->format('H:i')) }}">
                        <small class="text-muted">এই সময়ের পরে চেক-আউট হাজিরা নেওয়া বন্ধ।</small>
                        @error('teacher_check_out_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-info-circle"></i> গুরুত্বপূর্ণ তথ্য</h5>
        <p class="mb-1">এই সেটিংস অনুযায়ী বায়োমেট্রিক মেশিন থেকে আসা হাজিরা স্বয়ংক্রিয়ভাবে প্রক্রিয়া করা হবে।</p>
        <p class="mb-1">মোবাইল অ্যাপ থেকে দেওয়া হাজিরার ক্ষেত্রেও একই নিয়ম প্রযোজ্য হবে।</p>
        <p class="mb-0">যদি কোনো শিক্ষার্থী মেশিনে পাঞ্চ করে থাকে, মোবাইল অ্যাপ থেকে তার হাজিরা পরিবর্তন করা যাবে না।</p>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">
        <i class="fas fa-save mr-1"></i> সেটিংস সংরক্ষণ করুন
    </button>
</form>
@endsection
