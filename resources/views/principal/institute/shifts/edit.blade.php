@extends('layouts.admin')
@section('title','শিফট সম্পাদনা')
@section('content')
<div class="row mb-2"><div class="col"><h1 class="m-0">শিফট সম্পাদনা</h1></div><div class="col text-right"><a href="{{ route('principal.institute.shifts.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a></div></div>
<div class="card"><div class="card-body">
<form method="post" action="{{ route('principal.institute.shifts.update',[$school,$shift]) }}">@csrf @method('put')
  <div class="form-row">
    <div class="form-group col-md-4"><label>নাম *</label><input type="text" name="name" class="form-control" required value="{{ old('name',$shift->name) }}"></div>
    <div class="form-group col-md-3"><label>শুরু</label><input type="time" name="start_time" class="form-control" value="{{ old('start_time',$shift->start_time) }}"></div>
    <div class="form-group col-md-3"><label>শেষ</label><input type="time" name="end_time" class="form-control" value="{{ old('end_time',$shift->end_time) }}"></div>
    <div class="form-group col-md-2"><label>স্ট্যাটাস</label><select name="status" class="form-control"><option value="active" @if($shift->status=='active') selected @endif>active</option><option value="inactive" @if($shift->status=='inactive') selected @endif>inactive</option></select></div>
  </div>
  <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> আপডেট</button>
</form>
</div></div>
@endsection