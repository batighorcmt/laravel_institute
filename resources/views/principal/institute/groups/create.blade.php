@extends('layouts.admin')
@section('title','গ্রুপ যোগ')
@section('content')
<div class="row mb-2"><div class="col"><h1 class="m-0">নতুন গ্রুপ</h1></div><div class="col text-right"><a href="{{ route('principal.institute.groups.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a></div></div>
<div class="card"><div class="card-body">
<form method="post" action="{{ route('principal.institute.groups.store',$school) }}">@csrf
  <div class="form-row">
    <div class="form-group col-md-8"><label>নাম *</label><input type="text" name="name" class="form-control" required value="{{ old('name') }}"></div>
    <div class="form-group col-md-4"><label>স্ট্যাটাস</label><select name="status" class="form-control"><option value="active">active</option><option value="inactive">inactive</option></select></div>
  </div>
  <button class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
</form>
</div></div>
@endsection