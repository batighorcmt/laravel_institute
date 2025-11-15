@extends('layouts.admin')
@section('title','শিক্ষাবর্ষ সম্পাদনা')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">শিক্ষাবর্ষ সম্পাদনা</h1>
  <a href="{{ route('principal.institute.academic-years.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('principal.institute.academic-years.update',[$school,$academic_year]) }}">@csrf @method('PUT')
  <div class="form-row">
    <div class="form-group col-md-4"><label>নাম *</label><input type="text" name="name" class="form-control" required value="{{ old('name',$academic_year->name) }}"></div>
    <div class="form-group col-md-4"><label>শুরুর তারিখ *</label><input type="date" name="start_date" class="form-control" required value="{{ old('start_date',$academic_year->start_date->toDateString()) }}"></div>
    <div class="form-group col-md-4"><label>শেষ তারিখ *</label><input type="date" name="end_date" class="form-control" required value="{{ old('end_date',$academic_year->end_date->toDateString()) }}"></div>
  </div>
  <div class="form-group form-check">
    <input type="checkbox" name="is_current" id="is_current" class="form-check-input" value="1" {{ old('is_current',$academic_year->is_current)?'checked':'' }}>
    <label for="is_current" class="form-check-label">বর্তমান শিক্ষাবর্ষ হিসেবে নির্ধারণ</label>
  </div>
  <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> আপডেট</button>
</form>
@endsection