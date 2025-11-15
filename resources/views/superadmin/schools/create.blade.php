@extends('layouts.admin')
@section('title', 'নতুন স্কুল')
@section('content')
<div class="row mb-2">
    <div class="col-sm-6"><h1 class="m-0">নতুন স্কুল যোগ</h1></div>
    <div class="col-sm-6 text-right"><a href="{{ route('superadmin.schools.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে যান</a></div>
</div>

@if($errors->any())
 <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card">
 <div class="card-body">
  <form action="{{ route('superadmin.schools.store') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>স্কুলের নাম *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
      </div>
      <div class="form-group col-md-6">
        <label>কোড *</label>
        <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>ফোন</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
      </div>
      <div class="form-group col-md-6">
        <label>ইমেইল</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>ওয়েবসাইট</label>
        <input type="url" name="website" class="form-control" value="{{ old('website') }}">
      </div>
      <div class="form-group col-md-6">
        <label>স্ট্যাটাস *</label>
        <select name="status" class="form-control" required>
          <option value="active" {{ old('status')=='active'?'selected':'' }}>সক্রিয়</option>
          <option value="inactive" {{ old('status')=='inactive'?'selected':'' }}>নিষ্ক্রিয়</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>ঠিকানা</label>
      <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
    </div>
    <div class="form-group">
      <label>বর্ণনা</label>
      <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
    </div>
    <div class="form-group">
      <label>লোগো (PNG/JPG)</label>
      <input type="file" name="logo" class="form-control-file">
    </div>
    <button class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
  </form>
 </div>
</div>
@endsection
