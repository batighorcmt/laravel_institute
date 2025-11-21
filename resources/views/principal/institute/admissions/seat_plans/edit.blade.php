@extends('layouts.admin')
@section('title','সীট প্ল্যান এডিট')
@section('content')
<h4 class="mb-3">সীট প্ল্যান এডিট</h4>
<form method="POST" action="{{ route('principal.institute.admissions.seat-plans.update',[$school,$seatPlan]) }}">
    @csrf
    @method('PUT')
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-5">
                    <label>পরীক্ষা *</label>
                    <select name="exam_id" class="form-control" required>
                        <option value="">-- নির্বাচন করুন --</option>
                        @foreach($exams as $ex)
                            <option value="{{ $ex->id }}" {{ $seatPlan->exam_id==$ex->id?'selected':'' }}>{{ $ex->name }} ({{ $ex->type==='subject'?'প্রতি বিষয়':'সামগ্রীক' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>প্ল্যান নাম *</label>
                    <input name="name" class="form-control" required value="{{ old('name',$seatPlan->name) }}">
                </div>
                <div class="form-group col-md-3">
                    <label>শিফট</label>
                    <input name="shift" class="form-control" value="{{ old('shift',$seatPlan->shift) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>স্ট্যাটাস</label>
                    <select name="status" class="form-control">
                        @foreach(['active'=>'সক্রিয়','inactive'=>'নিষ্ক্রিয়','completed'=>'সম্পন্ন'] as $k=>$v)
                            <option value="{{ $k }}" {{ $seatPlan->status===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <button class="btn btn-success">আপডেট</button>
    <a href="{{ route('principal.institute.admissions.seat-plans.index',$school) }}" class="btn btn-secondary">ফিরে যান</a>
</form>
@endsection