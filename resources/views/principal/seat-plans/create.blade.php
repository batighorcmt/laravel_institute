@extends('layouts.admin')

@section('title', 'নতুন সিট প্ল্যান তৈরি করুন')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">নতুন সিট প্ল্যান তৈরি করুন</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.index', $school) }}">সিট প্ল্যান</a></li>
                    <li class="breadcrumb-item active">নতুন তৈরি</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">সিট প্ল্যানের তথ্য</h3>
            </div>
            <form action="{{ route('principal.institute.seat-plans.store', $school) }}" method="POST" id="seatPlanForm">
                @csrf
                <div class="card-body">
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
                                <label for="name">সিট প্ল্যানের নাম <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">উদাহরণ: প্রথম সাময়িক পরীক্ষা ২০২৫</small>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="shift">শিফট <span class="text-danger">*</span></label>
                                <select name="shift" id="shift" class="form-control @error('shift') is-invalid @enderror" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    <option value="morning" {{ old('shift') == 'morning' ? 'selected' : '' }}>সকাল</option>
                                    <option value="day" {{ old('shift') == 'day' ? 'selected' : '' }}>দিন</option>
                                </select>
                                @error('shift')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">অবস্থা <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>খসড়া</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>সক্রিয়</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>সম্পন্ন</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>শ্রেণি নির্বাচন করুন <span class="text-danger">*</span></label>
                        <div class="row">
                            @foreach($classes as $class)
                                <div class="col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="classes[]" value="{{ $class->id }}" id="class_{{ $class->id }}" class="custom-control-input" {{ in_array($class->id, old('classes', [])) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="class_{{ $class->id }}">
                                            {{ $class->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('classes')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>পরীক্ষা নির্বাচন করুন</label>
                        <div class="row">
                            @foreach($exams as $exam)
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="exams[]" value="{{ $exam->id }}" id="exam_{{ $exam->id }}" class="custom-control-input" {{ in_array($exam->id, old('exams', [])) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="exam_{{ $exam->id }}">
                                            {{ $exam->name }} ({{ $exam->class->name }})
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <small class="form-text text-muted">এই সিট প্ল্যানটি কোন পরীক্ষার জন্য ব্যবহার করা হবে?</small>
                    </div>

                    <div class="form-group">
                        <label for="description">বিবরণ</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="submitSeatPlanBtn">
                        <i class="fas fa-save"></i> সংরক্ষণ করুন
                    </button>
                    <a href="{{ route('principal.institute.seat-plans.index', $school) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> বাতিল
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    let isSubmitting = false;
    
    $('#seatPlanForm').on('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        
        isSubmitting = true;
        $('#submitSeatPlanBtn').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> প্রক্রিয়াধীন...');
        
        setTimeout(function() {
            isSubmitting = false;
            $('#submitSeatPlanBtn').prop('disabled', false)
                .html('<i class="fas fa-save"></i> সংরক্ষণ করুন');
        }, 5000);
    });
});
</script>
@endpush
@endsection
