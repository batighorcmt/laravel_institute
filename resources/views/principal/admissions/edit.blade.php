@extends('layouts.admin')
@section('title','Edit Application')
@section('content')
<div class="mb-3">
    <h1 class="h4">আবেদন সম্পাদনা</h1>
</div>
@if(session('errors'))
    @php($errs = session('errors'))
    <div class="alert alert-danger">
        <ul class="mb-0 small">
            @foreach($errs->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
<form action="{{ route('principal.institute.admissions.applications.update', [$school->id, $application->id]) }}" method="post" class="card">
    @csrf
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>নাম (English) *</label>
                <input name="name_en" value="{{ old('name_en',$application->name_en) }}" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label>নাম (Bangla) *</label>
                <input name="name_bn" value="{{ old('name_bn',$application->name_bn) }}" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label>পিতার নাম *</label>
                <input name="father_name_en" value="{{ old('father_name_en',$application->father_name_en) }}" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label>মাতার নাম *</label>
                <input name="mother_name_en" value="{{ old('mother_name_en',$application->mother_name_en) }}" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label>অভিভাবক</label>
                <input name="guardian_name_en" value="{{ old('guardian_name_en',$application->guardian_name_en) }}" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label>লিঙ্গ *</label>
                <select name="gender" class="form-control" required>
                    @foreach(['Male'=>'Male','Female'=>'Female','Other'=>'Other'] as $k=>$v)
                        <option value="{{ $k }}" @selected(old('gender',$application->gender)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>ধর্ম</label>
                <input name="religion" value="{{ old('religion',$application->religion) }}" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label>জন্ম তারিখ</label>
                <input type="date" name="dob" value="{{ old('dob',optional($application->dob)->format('Y-m-d')) }}" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label>মোবাইল *</label>
                <input name="mobile" value="{{ old('mobile',$application->mobile) }}" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label>ক্লাস</label>
                <input name="class_name" value="{{ old('class_name',$application->class_name) }}" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label>পূর্ববর্তী স্কুল</label>
                <input name="last_school" value="{{ old('last_school',$application->last_school) }}" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label>ফলাফল</label>
                <input name="result" value="{{ old('result',$application->result) }}" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label>পাসের বছর</label>
                <input name="pass_year" value="{{ old('pass_year',$application->pass_year) }}" class="form-control">
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('principal.institute.admissions.applications.show', [$school->id,$application->id]) }}" class="btn btn-outline-secondary">পেছনে</a>
        <button class="btn btn-primary">আপডেট সংরক্ষণ</button>
    </div>
</form>
@endsection