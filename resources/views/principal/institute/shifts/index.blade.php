@extends('layouts.admin')
@section('title','শিফটসমূহ')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">শিফটসমূহ - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.shifts.create', $school) }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> নতুন শিফট</a>
</div>
<div class="card">
  <div class="card-body">
    <form class="form-inline mb-3" method="get">
      <input type="text" name="q" value="{{ $q }}" class="form-control mr-2" placeholder="সার্চ...">
      <button class="btn btn-outline-secondary">সার্চ</button>
    </form>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>#</th><th>নাম</th><th>সময়</th><th>স্ট্যাটাস</th><th class="text-right">অ্যাকশন</th></tr></thead>
        <tbody>
          @forelse($items as $i => $item)
            <tr>
              <td>{{ $items->firstItem() + $i }}</td>
              <td>{{ $item->name }}</td>
              <td>{{ $item->start_time }} - {{ $item->end_time }}</td>
              <td><span class="badge badge-{{ $item->status=='active'?'success':'secondary' }}">{{ $item->status }}</span></td>
              <td class="text-right">
                <a href="{{ route('principal.institute.shifts.edit', [$school,$item]) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                <form action="{{ route('principal.institute.shifts.destroy', [$school,$item]) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                  @csrf @method('delete')
                  <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted">কিছু পাওয়া যায়নি</td></tr>
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
