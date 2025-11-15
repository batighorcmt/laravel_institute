@extends('layouts.admin')
@section('title','বিশেষ দল / গ্রুপ')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">বিশেষ দল / গ্রুপ তালিকা - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.teams.create',$school) }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> নতুন দল</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form class="form-inline mb-3" method="get">
  <input type="text" name="q" value="{{ $q }}" class="form-control mr-2" placeholder="নাম সার্চ...">
  <button class="btn btn-outline-secondary">সার্চ</button>
</form>
<div class="table-responsive">
  <table class="table table-bordered table-sm">
    <thead class="thead-light">
      <tr>
        <th style="width:60px">#</th>
        <th>নাম</th>
        <th>ধরণ</th>
        <th>সদস্য সংখ্যা</th>
        <th>স্ট্যাটাস</th>
        <th style="width:140px">অ্যাকশন</th>
      </tr>
    </thead>
    <tbody>
      @foreach($teams as $i=>$team)
        <tr>
          <td>{{ $teams->firstItem()+$i }}</td>
          <td>{{ $team->name }}</td>
          <td>{{ $team->type ?: '-' }}</td>
          <td>{{ $team->students()->count() }}</td>
          <td>
            <span class="badge badge-{{ $team->status==='active'?'success':'secondary' }}">{{ $team->status }}</span>
          </td>
          <td class="text-nowrap">
            <a href="{{ route('principal.institute.teams.add-students',[$school,$team]) }}" class="btn btn-sm btn-outline-success" title="শিক্ষার্থী যুক্ত করুন"><i class="fas fa-user-plus"></i></a>
            <a href="{{ route('principal.institute.teams.edit',[$school,$team]) }}" class="btn btn-sm btn-outline-primary" title="এডিট"><i class="fas fa-edit"></i></a>
            <form action="{{ route('principal.institute.teams.destroy',[$school,$team]) }}" method="post" class="d-inline" onsubmit="return confirm('মুছে ফেলতে চাই?');">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-outline-danger" title="মুছে ফেলুন"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
<div class="d-flex justify-content-between align-items-center mt-2">
  <div>মোট {{ $teams->total() }}টির মধ্যে {{ $teams->firstItem() }}–{{ $teams->lastItem() }}</div>
  <div>{{ $teams->links() }}</div>
</div>
@endsection