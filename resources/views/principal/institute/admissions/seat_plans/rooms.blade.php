@extends('layouts.admin')
@section('title','রুম ব্যবস্থাপনা')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">রুম ব্যবস্থাপনা: {{ $seatPlan->name }}</h4>
    <div class="d-flex" style="gap:6px;">
        <a href="{{ route('principal.institute.admissions.seat-plans.edit',[$school,$seatPlan]) }}" class="btn btn-sm btn-outline-primary">Edit Plan</a>
        <form method="POST" action="{{ route('principal.institute.admissions.seat-plans.destroy',[$school,$seatPlan]) }}" onsubmit="return confirm('প্ল্যান মুছলে সব রুম ও বরাদ্দ মুছে যাবে, নিশ্চিত?');">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Delete Plan</button>
        </form>
        <a href="{{ route('principal.institute.admissions.seat-plans.index',$school) }}" class="btn btn-sm btn-secondary">Back</a>
    </div>
</div>
<form method="POST" action="{{ route('principal.institute.admissions.seat-plans.rooms.store',[$school,$seatPlan]) }}" class="mb-3">
    @csrf
    <div class="form-row">
        <div class="form-group col-md-2"><input name="room_no" class="form-control" placeholder="রুম নং" required></div>
        <div class="form-group col-md-3"><input name="title" class="form-control" placeholder="টাইটেল"></div>
        <div class="form-group col-md-2"><input type="number" min="1" max="3" name="columns_count" class="form-control" value="3" placeholder="কলাম"></div>
        <div class="form-group col-md-1"><input type="number" min="0" name="col1_benches" class="form-control" placeholder="C1" value="0"></div>
        <div class="form-group col-md-1"><input type="number" min="0" name="col2_benches" class="form-control" placeholder="C2" value="0"></div>
        <div class="form-group col-md-1"><input type="number" min="0" name="col3_benches" class="form-control" placeholder="C3" value="0"></div>
        <div class="form-group col-md-2"><button class="btn btn-primary btn-block">যোগ করুন</button></div>
    </div>
</form>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm mb-0 table-striped">
            <thead><tr><th>#</th><th>রুম</th><th>টাইটেল</th><th>কলাম</th><th>Bench (C1/C2/C3)</th><th></th></tr></thead>
            <tbody>
            @forelse($seatPlan->rooms as $r)
                <tr>
                    <td>{{ $r->id }}</td><td>{{ $r->room_no }}</td><td>{{ $r->title }}</td><td>{{ $r->columns_count }}</td>
                    <td>{{ $r->col1_benches }}/{{ $r->col2_benches }}/{{ $r->col3_benches }}</td>
                    <td class="text-right d-flex justify-content-end" style="gap:6px;">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('principal.institute.admissions.seat-plans.rooms.allocate', [$school,$seatPlan,$r]) }}">Allocate</a>
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('principal.institute.admissions.seat-plans.rooms.print', [$school,$seatPlan,$r]) }}" target="_blank">Print</a>
                        <a class="btn btn-sm btn-outline-info" href="{{ route('principal.institute.admissions.seat-plans.rooms.edit', [$school,$r]) }}">Edit</a>
                        <form method="POST" action="{{ route('principal.institute.admissions.seat-plans.rooms.delete', [$school, $r]) }}" onsubmit="return confirm('মুছবেন?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4">কোন রুম নেই</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<a href="{{ route('principal.institute.admissions.seat-plans.index',$school) }}" class="btn btn-secondary mt-3">ফিরে যান</a>
@endsection