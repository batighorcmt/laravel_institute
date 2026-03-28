@extends('layouts.admin')

@section('title', 'হালনাগাদ পাবলিক পরীক্ষা')

@section('content_header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">পাবলিক পরীক্ষা হালনাগাদ করুন</h1>
    </div>
    <div class="col-sm-6 text-right">
        <a href="{{ route('principal.institute.public_exams.index', $school) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">ডাটা পরিবর্তন করুন</h3>
            </div>
            <form action="{{ route('principal.institute.public_exams.update', [$school, $publicExam]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">শর্ট নাম (Short Name) <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="short_name" class="form-control @error('short_name') is-invalid @enderror" value="{{ old('short_name', $publicExam->short_name) }}" placeholder="Ex: PSC, JSC, SSC, HSC" required>
                            @error('short_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">পূর্ণ নাম (Full Name)</label>
                        <div class="col-sm-9">
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $publicExam->full_name) }}" placeholder="Secondary School Certificate">
                            @error('full_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">স্ট্যাটাস <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $publicExam->status) == 'active' ? 'selected' : '' }}>সক্রিয় (Active)</option>
                                <option value="inactive" {{ old('status', $publicExam->status) == 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয় (Inactive)</option>
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">আপডেট করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
