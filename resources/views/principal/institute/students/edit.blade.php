@extends('layouts.admin')
@section('title','শিক্ষার্থী সম্পাদনা')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">শিক্ষার্থী সম্পাদনা</h1>
  <a href="{{ route('principal.institute.students.show',[$school,$student]) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> প্রোফাইল</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('principal.institute.students.update',[$school,$student]) }}">@csrf @method('PUT')
  <div class="form-row">
    <div class="form-group col-md-6"><label>নাম (English)</label><input type="text" name="student_name_en" class="form-control" value="{{ old('student_name_en',$student->student_name_en) }}"></div>
    <div class="form-group col-md-6"><label>নাম (বাংলা) *</label><input type="text" name="student_name_bn" class="form-control" required value="{{ old('student_name_bn',$student->student_name_bn) }}"></div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-3"><label>জন্ম তারিখ *</label><input type="date" name="date_of_birth" class="form-control" required value="{{ old('date_of_birth',$student->date_of_birth->toDateString()) }}"></div>
    <div class="form-group col-md-3"><label>লিঙ্গ *</label><select name="gender" class="form-control" required><option value="male" {{ old('gender',$student->gender)=='male'?'selected':'' }}>ছেলে</option><option value="female" {{ old('gender',$student->gender)=='female'?'selected':'' }}>মেয়ে</option></select></div>
    <div class="form-group col-md-3"><label>রক্তের গ্রুপ</label>
      <select name="blood_group" class="form-control">
        <option value="">--</option>
        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
          <option value="{{ $bg }}" {{ old('blood_group',$student->blood_group)==$bg?'selected':'' }}>{{ $bg }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group col-md-3"><label>ভর্তি তারিখ *</label><input type="date" name="admission_date" class="form-control" required value="{{ old('admission_date',$student->admission_date->toDateString()) }}"></div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-3"><label>পিতার নাম (Eng) *</label><input type="text" name="father_name" class="form-control" required value="{{ old('father_name',$student->father_name) }}"></div>
    <div class="form-group col-md-3"><label>পিতার নাম (বাংলা) *</label><input type="text" name="father_name_bn" class="form-control" required value="{{ old('father_name_bn',$student->father_name_bn) }}"></div>
    <div class="form-group col-md-3"><label>মাতার নাম (Eng) *</label><input type="text" name="mother_name" class="form-control" required value="{{ old('mother_name',$student->mother_name) }}"></div>
    <div class="form-group col-md-3"><label>মাতার নাম (বাংলা) *</label><input type="text" name="mother_name_bn" class="form-control" required value="{{ old('mother_name_bn',$student->mother_name_bn) }}"></div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-4"><label>অভিভাবকের ফোন *</label><input type="text" name="guardian_phone" class="form-control" required value="{{ old('guardian_phone',$student->guardian_phone) }}"></div>
    <div class="form-group col-md-4"><label>স্ট্যাটাস *</label><select name="status" class="form-control"><option value="active" {{ old('status',$student->status)=='active'?'selected':'' }}>active</option><option value="inactive" {{ old('status',$student->status)=='inactive'?'selected':'' }}>inactive</option><option value="graduated" {{ old('status',$student->status)=='graduated'?'selected':'' }}>graduated</option><option value="transferred" {{ old('status',$student->status)=='transferred'?'selected':'' }}>transferred</option></select></div>
    <div class="form-group col-md-4"><label>ঠিকানা *</label><textarea name="address" rows="1" class="form-control" required>{{ old('address',$student->address) }}</textarea></div>
  </div>
  <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> আপডেট</button>
</form>
@endsection