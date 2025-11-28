@extends('layouts.admin')
@section('title','সেকশনসমূহ')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">সেকশনসমূহ - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.sections.create', $school) }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> নতুন সেকশন</a>
</div>
<div class="card"><div class="card-body">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle"></i> {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif
  <form class="form-inline mb-3" method="get">
    <input type="text" class="form-control mr-2" name="q" value="{{ $q }}" placeholder="সার্চ...">
    <button class="btn btn-outline-secondary">সার্চ</button>
  </form>
  <table class="table table-striped">
  <thead><tr><th>#</th><th>শাখা</th><th>শ্রেণি</th><th>শ্রেণি শিক্ষক</th><th>স্ট্যাটাস</th><th class="text-right">অ্যাকশন</th></tr></thead>
    <tbody>
      @forelse($items as $i => $item)
      <tr>
        <td>{{ $items->firstItem() + $i }}</td>
        <td>{{ $item->name }}</td>
  <td>{{ optional($item->class)->name }}</td>
        <td>{{ $item->classTeacher?->user?->name ?? ($item->class_teacher_name ?? '-') }}</td>
        <td><span class="badge badge-{{ $item->status=='active'?'success':'secondary' }}">{{ $item->status }}</span></td>
        <td class="text-right">
          <a href="{{ route('principal.institute.sections.edit', [$school,$item]) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
          <form action="{{ route('principal.institute.sections.destroy', [$school,$item]) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">@csrf @method('delete')<button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></form>
        </td>
      </tr>
      @empty
  <tr><td colspan="6" class="text-center text-muted">কিছু পাওয়া যায়নি</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="d-flex justify-content-between align-items-center">
    <div>মোট {{ $items->total() }}টির মধ্যে {{ $items->firstItem() }}–{{ $items->lastItem() }}</div>
    <div>{{ $items->links() }}</div>
  </div>
</div></div>
@endsection