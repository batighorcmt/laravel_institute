@extends('layouts.admin')

@section('title', 'পরীক্ষা সম্পাদনা করুন')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">পরীক্ষা সম্পাদনা করুন</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.exams.index', $school) }}">পরীক্ষা তালিকা</a></li>
                    <li class="breadcrumb-item active">সম্পাদনা</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষার তথ্য সম্পাদনা করুন</h3>
            </div>
            <form action="{{ route('principal.institute.exams.update', [$school, $exam]) }}" method="POST" id="examEditForm">
                @csrf
                @method('PUT')
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
                                <label for="academic_year_id">শিক্ষাবর্ষ <span class="text-danger">*</span></label>
                                <select name="academic_year_id" id="academic_year_id" class="form-control @error('academic_year_id') is-invalid @enderror" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id', $exam->academic_year_id) == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="class_id">শ্রেণি <span class="text-danger">*</span></label>
                                <select name="class_id" id="class_id" class="form-control @error('class_id') is-invalid @enderror" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id', $exam->class_id) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">পরীক্ষার নাম (ইংরেজি) <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $exam->name) }}" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name_bn">পরীক্ষার নাম (বাংলা)</label>
                                <input type="text" name="name_bn" id="name_bn" class="form-control @error('name_bn') is-invalid @enderror" value="{{ old('name_bn', $exam->name_bn) }}">
                                @error('name_bn')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date">শুরুর তারিখ</label>
                                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $exam->start_date ? $exam->start_date->format('Y-m-d') : '') }}">
                                @error('start_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">শেষের তারিখ</label>
                                <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $exam->end_date ? $exam->end_date->format('Y-m-d') : '') }}">
                                @error('end_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">অবস্থা <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status', $exam->status) == 'draft' ? 'selected' : '' }}>খসড়া</option>
                                    <option value="active" {{ old('status', $exam->status) == 'active' ? 'selected' : '' }}>সক্রিয়</option>
                                    <option value="completed" {{ old('status', $exam->status) == 'completed' ? 'selected' : '' }}>সম্পন্ন</option>
                                    <option value="cancelled" {{ old('status', $exam->status) == 'cancelled' ? 'selected' : '' }}>বাতিল</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">বিবরণ</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $exam->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="updateBtn">
                        <i class="fas fa-save"></i> আপডেট করুন
                    </button>
                    <a href="{{ route('principal.institute.exams.show', [$school, $exam]) }}" class="btn btn-secondary">
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
    
    $('#examEditForm').on('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        
        isSubmitting = true;
        $('#updateBtn').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> প্রক্রিয়াধীন...');
        
        setTimeout(function() {
            isSubmitting = false;
            $('#updateBtn').prop('disabled', false)
                .html('<i class="fas fa-save"></i> আপডেট করুন');
        }, 5000);
    });
});
</script>
@endpush
@endsection
