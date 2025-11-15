@extends('layouts.admin')
@section('title','নতুন দল')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">নতুন বিশেষ দল / গ্রুপ</h1>
  <a href="{{ route('principal.institute.teams.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('principal.institute.teams.store',$school) }}">@csrf
  <div class="form-group">
    <label>নাম <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $team->name) }}" required>
  </div>
  <div class="form-group">
    <label>ধরণ</label>
    <input type="text" name="type" class="form-control" value="{{ old('type', $team->type) }}" placeholder="যেমন: club, extra-class">
  </div>
  <div class="form-group">
    <label>বর্ণনা</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $team->description) }}</textarea>
  </div>
  <div class="form-group">
    <label>স্ট্যাটাস</label>
    <select name="status" class="form-control">
      <option value="active" {{ old('status',$team->status)==='active'?'selected':'' }}>Active</option>
      <option value="inactive" {{ old('status',$team->status)==='inactive'?'selected':'' }}>Inactive</option>
    </select>
  </div>
  <button class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
</form>
@endsection