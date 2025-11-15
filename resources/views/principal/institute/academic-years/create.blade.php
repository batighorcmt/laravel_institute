@extends('layouts.admin')
@section('title','নতুন শিক্ষাবর্ষ')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">নতুন শিক্ষাবর্ষ</h1>
  <a href="{{ route('principal.institute.academic-years.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('principal.institute.academic-years.store',$school) }}">@csrf
  <div class="form-row">
    <div class="form-group col-md-4"><label>নাম *</label><input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="2025"></div>
    <div class="form-group col-md-4"><label>শুরুর তারিখ *</label><input type="date" name="start_date" class="form-control" required value="{{ old('start_date') }}"></div>
    <div class="form-group col-md-4"><label>শেষ তারিখ *</label><input type="date" name="end_date" class="form-control" required value="{{ old('end_date') }}"></div>
  </div>
  <div class="form-group form-check">
    <input type="checkbox" name="is_current" id="is_current" class="form-check-input" value="1" {{ old('is_current')?'checked':'' }}>
    <label for="is_current" class="form-check-label">এই শিক্ষাবর্ষকে বর্তমান হিসেবে নির্ধারণ</label>
  </div>
  <button class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
</form>
@endsection