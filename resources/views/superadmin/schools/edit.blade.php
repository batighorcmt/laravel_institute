@extends('layouts.admin')
@section('title', 'স্কুল সম্পাদনা')
@section('content')
<div class="row mb-2">
    <div class="col-sm-6"><h1 class="m-0">স্কুল সম্পাদনা</h1></div>
    <div class="col-sm-6 text-right"><a href="{{ route('superadmin.schools.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে যান</a></div>
</div>

@php($errorBag = ($errors instanceof \Illuminate\Support\ViewErrorBag) ? $errors : (session('errors') instanceof \Illuminate\Support\ViewErrorBag ? session('errors') : null))
@if($errorBag && $errorBag->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errorBag->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form action="{{ route('superadmin.schools.update', $school) }}" method="post" enctype="multipart/form-data">
<div class="card mb-3">
 <div class="card-header"><strong>প্রতিষ্ঠানের তথ্য</strong></div>
 <div class="card-body">
    @csrf
    @method('PUT')
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>স্কুলের নাম (ইংরেজি) *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $school->name) }}" required>
      </div>
      <div class="form-group col-md-6">
        <label>স্কুলের নাম (বাংলা)</label>
        <input type="text" name="name_bn" class="form-control" value="{{ old('name_bn', $school->name_bn) }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>কোড *</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $school->code) }}" required>
      </div>
      <div class="form-group col-md-6">
        <label>ফোন</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $school->phone) }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>স্ট্যাটাস *</label>
        <select name="status" class="form-control" required>
          <option value="active" {{ old('status', $school->status)=='active'?'selected':'' }}>সক্রিয়</option>
          <option value="inactive" {{ old('status', $school->status)=='inactive'?'selected':'' }}>নিষ্ক্রিয়</option>
        </select>
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
        <label>লোগো পরিবর্তন (PNG/JPG)</label>
        <input type="file" name="logo" class="form-control-file">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>ঠিকানা (ইংরেজি)</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address', $school->address) }}</textarea>
      </div>
      <div class="form-group col-md-6">
        <label>ঠিকানা (বাংলা)</label>
        <textarea name="address_bn" class="form-control" rows="2">{{ old('address_bn', $school->address_bn) }}</textarea>
      </div>
    </div>
    <div class="form-group">
      <label>বর্ণনা</label>
      <textarea name="description" class="form-control" rows="3">{{ old('description', $school->description) }}</textarea>
    </div>
 </div>
 </div>

<div class="card">
  <div class="card-header"><strong>প্রতিষ্ঠান প্রধানের তথ্য</strong></div>
  <div class="card-body">
    <div class="alert alert-info py-2">ইমেইলটি প্রতিষ্ঠান লগইনের জন্য এবং অ্যাডমিন (Principal) রোলের জন্য ব্যবহৃত হবে।</div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>প্রতিষ্ঠান প্রধানের নাম (ইংরেজি)</label>
        <input type="text" name="principal_name_en" class="form-control" value="{{ old('principal_name_en', optional($principal?->user)->first_name ?? optional($principal?->user)->name) }}">
      </div>
      <div class="form-group col-md-6">
        <label>প্রতিষ্ঠান প্রধানের নাম (বাংলা)</label>
        <input type="text" name="principal_name_bn" class="form-control" value="{{ old('principal_name_bn') }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>পদবী</label>
        <input type="text" name="principal_designation" class="form-control" value="{{ old('principal_designation', $principal->designation ?? 'প্রধান শিক্ষক') }}">
      </div>
      <div class="form-group col-md-6">
        <label>মোবাইল</label>
        <input type="text" name="principal_phone" class="form-control" value="{{ old('principal_phone', optional($principal?->user)->phone) }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>ইমেইল (লগইন)</label>
        <input type="email" name="principal_email" class="form-control" value="{{ old('principal_email', optional($principal?->user)->email) }}">
      </div>
    </div>
    <button class="btn btn-warning"><i class="fas fa-save mr-1"></i> আপডেট</button>
  </div>
 </div>
</div>
</form>
@endsection
