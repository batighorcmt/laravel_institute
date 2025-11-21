@extends('layouts.admin')
@section('title','রুম এডিট')
@section('content')
<h4 class="mb-3">রুম এডিট (প্ল্যান: {{ $seatPlan->name }})</h4>
<form method="POST" action="{{ route('principal.institute.admissions.seat-plans.rooms.update',[$school,$room]) }}">
    @csrf @method('PUT')
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-2"><label>রুম নং *</label><input name="room_no" class="form-control" required value="{{ old('room_no',$room->room_no) }}"></div>
                <div class="form-group col-md-3"><label>টাইটেল</label><input name="title" class="form-control" value="{{ old('title',$room->title) }}"></div>
                <div class="form-group col-md-2"><label>কলাম *</label><input type="number" min="1" max="3" name="columns_count" class="form-control" value="{{ old('columns_count',$room->columns_count) }}" required></div>
                <div class="form-group col-md-1"><label>C1</label><input type="number" min="0" name="col1_benches" class="form-control" value="{{ old('col1_benches',$room->col1_benches) }}"></div>
                <div class="form-group col-md-1"><label>C2</label><input type="number" min="0" name="col2_benches" class="form-control" value="{{ old('col2_benches',$room->col2_benches) }}"></div>
                <div class="form-group col-md-1"><label>C3</label><input type="number" min="0" name="col3_benches" class="form-control" value="{{ old('col3_benches',$room->col3_benches) }}"></div>
            </div>
        </div>
    </div>
    <button class="btn btn-success">আপডেট</button>
    <a href="{{ route('principal.institute.admissions.seat-plans.rooms',[$school,$seatPlan]) }}" class="btn btn-secondary">ফিরে যান</a>
</form>
@endsection