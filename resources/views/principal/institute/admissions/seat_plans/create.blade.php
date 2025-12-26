@extends('layouts.admin')
@section('title','নতুন সীট প্ল্যান')
@section('content')
<h4 class="mb-3">নতুন সীট প্ল্যান</h4>
<form method="POST" action="{{ route('principal.institute.admissions.seat-plans.store',$school) }}">
    @csrf
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-5">
                    <label>পরীক্ষা সমূহ *</label>
                    <select name="exam_ids[]" class="form-control" multiple required>
                        @foreach($exams as $ex)
                            <option value="{{ $ex->id }}">{{ $ex->name }} — {{ $ex->class_name ?? 'শ্রেণি নেই' }} ({{ $ex->type==='subject'?'প্রতি বিষয়':'সামগ্রীক' }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted">একাধিক পরীক্ষা নির্বাচন করুন</small>
                </div>
                <div class="form-group col-md-4">
                    <label>প্ল্যান নাম *</label>
                    <input name="name" class="form-control" required value="{{ old('name') }}">
                </div>
                <div class="form-group col-md-3">
                    <label>শিফট</label>
                    <input name="shift" class="form-control" value="{{ old('shift','Morning') }}">
                </div>
            </div>
        </div>
    </div>
    <button class="btn btn-success">সংরক্ষণ</button>
    <a href="{{ route('principal.institute.admissions.seat-plans.index',$school) }}" class="btn btn-secondary">ফিরে যান</a>
</form>
@endsection