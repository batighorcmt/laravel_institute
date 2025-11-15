@extends('layouts.admin')
@section('title','গ্রুপ হালনাগাদ')
@section('content')
<div class="row mb-2"><div class="col"><h1 class="m-0">গ্রুপ সম্পাদনা</h1></div><div class="col text-right"><a href="{{ route('principal.institute.groups.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a></div></div>
<div class="card"><div class="card-body">
<form method="post" action="{{ route('principal.institute.groups.update',[$school,$group]) }}">@csrf @method('PUT')
  <div class="form-row">
    <div class="form-group col-md-8"><label>নাম *</label><input type="text" name="name" class="form-control" required value="{{ old('name',$group->name) }}"></div>
    <div class="form-group col-md-4"><label>স্ট্যাটাস</label><select name="status" class="form-control">
      <option value="active" {{ $group->status==='active'?'selected':'' }}>active</option>
      <option value="inactive" {{ $group->status==='inactive'?'selected':'' }}>inactive</option>
    </select></div>
  </div>
  <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> আপডেট</button>
</form>
</div></div>
@endsection