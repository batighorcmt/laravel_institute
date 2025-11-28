@extends('layouts.admin')
@section('title','সেকশন সম্পাদনা')
@section('content')
<div class="row mb-2"><div class="col"><h1 class="m-0">সেকশন সম্পাদনা</h1></div><div class="col text-right"><a href="{{ route('principal.institute.sections.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a></div></div>
<div class="card"><div class="card-body">
<form method="post" action="{{ route('principal.institute.sections.update',[$school,$section]) }}">@csrf @method('put')
  <div class="form-row">
    <div class="form-group col-md-4"><label>শ্রেণি *</label>
      <select name="class_id" class="form-control" required>
        @foreach($classList as $cls)
          <option value="{{ $cls->id }}" {{ old('class_id',$section->class_id)==$cls->id?'selected':'' }}>{{ $cls->name }} ({{ $cls->numeric_value }})</option>
        @endforeach
      </select>
    </div>
    <div class="form-group col-md-4"><label>শাখার নাম *</label><input type="text" name="name" class="form-control" required value="{{ old('name',$section->name) }}"></div>
    <div class="form-group col-md-4">
      <label>শ্রেণি শিক্ষক (ঐচ্ছিক)</label>
      <select name="class_teacher_id" class="form-control">
        <option value="">-- নির্বাচন করুন --</option>
        @foreach($activeTeachers as $t)
          <option value="{{ $t->id }}" {{ old('class_teacher_id',$section->class_teacher_id)==$t->id?'selected':'' }}>
            {{ $t->user->name ?? ('Teacher #'.$t->id) }} ({{ $t->user->username ?? $t->user->email ?? '' }})
          </option>
        @endforeach
      </select>
      <small class="text-muted d-block mt-1">একজন শিক্ষক কেবল একটি শাখায় শ্রেণি শিক্ষক হতে পারবেন। ফাঁকা রাখাও যাবে।</small>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-3"><label>স্ট্যাটাস</label><select name="status" class="form-control"><option value="active" @if($section->status=='active') selected @endif>active</option><option value="inactive" @if($section->status=='inactive') selected @endif>inactive</option></select></div>
  </div>
  <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> আপডেট</button>
</form>
</div></div>
@endsection