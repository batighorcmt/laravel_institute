@extends('layouts.admin')
@section('title', 'স্কুল সম্পাদনা')
@section('content')
<div class="row mb-2">
    <div class="col-sm-6"><h1 class="m-0">স্কুল সম্পাদনা</h1></div>
    <div class="col-sm-6 text-right"><a href="{{ route('superadmin.schools.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে যান</a></div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card">
 <div class="card-body">
  <form action="{{ route('superadmin.schools.update', $school) }}" method="post" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>স্কুলের নাম *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $school->name) }}" required>
      </div>
      <div class="form-group col-md-6">
        <label>কোড *</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $school->code) }}" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>ফোন</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $school->phone) }}">
      </div>
      <div class="form-group col-md-6">
        <label>ইমেইল</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $school->email) }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>ওয়েবসাইট</label>
        <input type="url" name="website" class="form-control" value="{{ old('website', $school->website) }}">
      </div>
      <div class="form-group col-md-6">
        <label>স্ট্যাটাস *</label>
        <select name="status" class="form-control" required>
          <option value="active" {{ old('status', $school->status)=='active'?'selected':'' }}>সক্রিয়</option>
          <option value="inactive" {{ old('status', $school->status)=='inactive'?'selected':'' }}>নিষ্ক্রিয়</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>ঠিকানা</label>
      <textarea name="address" class="form-control" rows="2">{{ old('address', $school->address) }}</textarea>
    </div>
    <div class="form-group">
      <label>বর্ণনা</label>
      <textarea name="description" class="form-control" rows="3">{{ old('description', $school->description) }}</textarea>
    </div>
    <div class="form-group">
      <label>বর্তমান লোগো</label><br>
      @if($school->logo)
        <img src="{{ Storage::url($school->logo) }}" alt="logo" width="64" class="mb-2 rounded">
      @else
        <span class="text-muted">নেই</span>
      @endif
      <div class="mt-2">
        <label>লোগো পরিবর্তন (PNG/JPG)</label>
        <input type="file" name="logo" class="form-control-file">
      </div>
    </div>
    <button class="btn btn-warning"><i class="fas fa-save mr-1"></i> আপডেট</button>
  </form>
 </div>
</div>
@endsection
