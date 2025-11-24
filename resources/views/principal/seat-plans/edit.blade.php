@extends('layouts.admin')

@section('title', 'Edit Seat Plan - ' . $seatPlan->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit Seat Plan</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.index', $school) }}">Seat Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.show', [$school, $seatPlan]) }}">{{ $seatPlan->name }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title">Seat Plan Information</h3>
            </div>
            <form action="{{ route('principal.institute.seat-plans.update', [$school, $seatPlan]) }}" method="POST" id="seatPlanForm">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Seat Plan Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $seatPlan->name) }}" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Example: First Terminal Exam 2025</small>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="shift">Shift <span class="text-danger">*</span></label>
                                <select name="shift" id="shift" class="form-control @error('shift') is-invalid @enderror" required>
                                    <option value="">-- Select --</option>
                                    <option value="Morning" {{ old('shift', $seatPlan->shift) == 'Morning' ? 'selected' : '' }}>Morning</option>
                                    <option value="Afternoon" {{ old('shift', $seatPlan->shift) == 'Afternoon' ? 'selected' : '' }}>Afternoon</option>
                                </select>
                                @error('shift')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status', $seatPlan->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="active" {{ old('status', $seatPlan->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="completed" {{ old('status', $seatPlan->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Select Classes <span class="text-danger">*</span></label>
                        <div class="row">
                            @php
                                $selectedClassIds = old('class_ids', $seatPlan->seatPlanClasses->pluck('class_id')->toArray());
                            @endphp
                            @foreach($classes as $class)
                                <div class="col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" id="class_{{ $class->id }}" class="custom-control-input" {{ in_array($class->id, $selectedClassIds) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="class_{{ $class->id }}">
                                            {{ $class->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('class_ids')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Select the classes that will participate in this seat plan</small>
                    </div>

                    <div class="form-group">
                        <label>Select Exams (Optional)</label>
                        <div class="row">
                            @php
                                $selectedExamIds = old('exam_ids', $seatPlan->seatPlanExams->pluck('exam_id')->toArray());
                            @endphp
                            @if($exams->count() > 0)
                                @foreach($exams as $exam)
                                    <div class="col-md-4">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="exam_ids[]" value="{{ $exam->id }}" id="exam_{{ $exam->id }}" class="custom-control-input" {{ in_array($exam->id, $selectedExamIds) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="exam_{{ $exam->id }}">
                                                {{ $exam->name }} - {{ $exam->class->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No exams available. You can link exams to this seat plan later.</p>
                                </div>
                            @endif
                        </div>
                        @error('exam_ids')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Link this seat plan to specific exams if needed</small>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" id="submitBtn" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Seat Plan
                    </button>
                    <a href="{{ route('principal.institute.seat-plans.show', [$school, $seatPlan]) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
// Prevent double submission
let isSubmitting = false;
document.getElementById('seatPlanForm').addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    setTimeout(() => {
        isSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Seat Plan';
    }, 5000);
});
</script>
@endpush
@endsection
