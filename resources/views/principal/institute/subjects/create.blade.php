@extends('layouts.admin')
@section('title','বিষয় যোগ')
@section('content')
<div class="row mb-2"><div class="col"><h1 class="m-0">নতুন বিষয়</h1></div><div class="col text-right"><a href="{{ route('principal.institute.subjects.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a></div></div>
<div class="card"><div class="card-body">
<form method="post" action="{{ route('principal.institute.subjects.store',$school) }}">@csrf
  <div class="form-row">
    <div class="form-group col-md-5"><label>নাম *</label><input type="text" name="name" class="form-control" required value="{{ old('name') }}"></div>
    <div class="form-group col-md-3"><label>কোড</label><input type="text" name="code" class="form-control" value="{{ old('code') }}"></div>
    <div class="form-group col-md-2"><label>স্ট্যাটাস</label><select name="status" class="form-control"><option value="active" {{ old('status')=='active'?'selected':'' }}>active</option><option value="inactive" {{ old('status')=='inactive'?'selected':'' }}>inactive</option></select></div>
  </div>
  <div class="form-group">
    <label>বর্ণনা</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
  </div>
  <hr>
  <h5 class="mb-2">অংশ নির্বাচন</h5>
  <div class="form-row">
    <div class="form-group col-md-2">
      <div class="custom-control custom-checkbox mt-4">
        <input type="checkbox" class="custom-control-input part-toggle" id="has_creative" name="has_creative" value="1" {{ old('has_creative')?'checked':'' }}>
        <label class="custom-control-label" for="has_creative">সৃজনশীল</label>
      </div>
    </div>
    <div class="form-group col-md-2">
      <div class="custom-control custom-checkbox mt-4">
        <input type="checkbox" class="custom-control-input part-toggle" id="has_mcq" name="has_mcq" value="1" {{ old('has_mcq')?'checked':'' }}>
        <label class="custom-control-label" for="has_mcq">বহুনির্বাচনী</label>
      </div>
    </div>
    <div class="form-group col-md-2">
      <div class="custom-control custom-checkbox mt-4">
        <input type="checkbox" class="custom-control-input part-toggle" id="has_practical" name="has_practical" value="1" {{ old('has_practical')?'checked':'' }}>
        <label class="custom-control-label" for="has_practical">ব্যবহারিক</label>
      </div>
    </div>
  </div>
  <div class="mb-3">
    <small class="text-muted">শুধু অংশ নির্বাচন সংরক্ষণ হবে। মার্ক/পাস সেটিং পরে কনফিগার করা যাবে।</small>
  </div>
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif
  <button class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
</form>
</div></div>
@endsection