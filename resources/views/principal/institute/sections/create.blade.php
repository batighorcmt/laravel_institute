@extends('layouts.admin')
@section('title','সেকশন যোগ')
@section('content')
<div class="row mb-2"><div class="col"><h1 class="m-0">নতুন সেকশন</h1></div><div class="col text-right"><a href="{{ route('principal.institute.sections.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a></div></div>
<div class="card"><div class="card-body">
<form method="post" action="{{ route('principal.institute.sections.store',$school) }}">@csrf
  <div class="form-row">
    <div class="form-group col-md-4"><label>শ্রেণি *</label>
      <select name="class_id" class="form-control" required>
        <option value="">-- নির্বাচন করুন --</option>
        @foreach($classList as $cls)
          <option value="{{ $cls->id }}" {{ old('class_id')==$cls->id?'selected':'' }}>{{ $cls->name }} ({{ $cls->numeric_value }})</option>
        @endforeach
      </select>
    </div>
    <div class="form-group col-md-4"><label>শাখার নাম *</label><input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="যেমন: A"></div>
    <div class="form-group col-md-4"><label>শ্রেণি শিক্ষকের নাম</label><input type="text" name="class_teacher_name" class="form-control" value="{{ old('class_teacher_name') }}" placeholder="শিক্ষকের নাম"></div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-3"><label>স্ট্যাটাস</label><select name="status" class="form-control"><option value="active">active</option><option value="inactive">inactive</option></select></div>
  </div>
  <button class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
</form>
</div></div>
@endsection