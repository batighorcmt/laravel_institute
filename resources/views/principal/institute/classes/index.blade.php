@extends('layouts.admin')
@section('title','ক্লাসসমূহ')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">ক্লাসসমূহ - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.classes.create', $school) }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> নতুন ক্লাস</a>
  
</div>
<div class="card">
  <div class="card-body">
    <form class="form-inline mb-3" method="get">
      <input type="text" name="q" value="{{ $q }}" class="form-control mr-2" placeholder="নাম বা নম্বর দ্বারা সার্চ...">
      <button class="btn btn-outline-secondary">সার্চ</button>
    </form>
    <div class="table-responsive">
      <table class="table table-striped">
  <thead><tr><th>#</th><th>নাম</th><th>নিউমেরিক</th><th>আসন</th><th>স্ট্যাটাস</th><th>বিষয়</th><th class="text-right">অ্যাকশন</th></tr></thead>
        <tbody>
          @forelse($items as $i => $cls)
            <tr>
              <td>{{ $items->firstItem() + $i }}</td>
              <td>{{ $cls->name }}</td>
              <td>{{ $cls->numeric_value }}</td>
              <td>{{ $cls->capacity }}</td>
              <td><span class="badge badge-{{ $cls->status==='active'?'success':'secondary' }}">{{ $cls->status }}</span></td>
              <td>
                <a href="{{ route('principal.institute.classes.subjects.index',[$school,$cls]) }}" class="btn btn-sm btn-info"><i class="fas fa-book-open mr-1"></i> বিষয়</a>
              </td>
              <td class="text-right">
                <a href="{{ route('principal.institute.classes.edit',[$school,$cls]) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                <form action="{{ route('principal.institute.classes.destroy',[$school,$cls]) }}" method="post" class="d-inline" onsubmit="return confirm('মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">কোনও ক্লাস নেই</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-between align-items-center">
      <div>মোট {{ $items->total() }}টির মধ্যে {{ $items->firstItem() }}–{{ $items->lastItem() }}</div>
      <div>{{ $items->links() }}</div>
    </div>
  </div>
</div>
@endsection