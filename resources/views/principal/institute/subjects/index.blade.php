@extends('layouts.admin')
@section('title','বিষয়সমূহ')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">বিষয়সমূহ - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.subjects.create', $school) }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> নতুন বিষয়</a>
</div>
<div class="card"><div class="card-body">
  <form class="form-inline mb-3" method="get">
    <input type="text" class="form-control mr-2" name="q" value="{{ $q }}" placeholder="সার্চ (নাম/কোড)...">
    <button class="btn btn-outline-secondary">সার্চ</button>
  </form>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>#</th><th>নাম</th><th>কোড</th><th>প্যাটার্ন</th><th>স্ট্যাটাস</th><th class="text-right">অ্যাকশন</th></tr></thead>
      <tbody>
        @forelse($subjects as $i => $item)
        <tr>
          <td>{{ $subjects->firstItem() + $i }}</td>
          <td>{{ $item->name }}</td>
          <td>{{ $item->code }}</td>
          <td>
            @php($shown=false)
            @if($item->has_creative)
              @php($shown=true)
              <span class="badge badge-info">সৃজনশীল</span>
            @endif
            @if($item->has_mcq)
              @php($shown=true)
              <span class="badge badge-primary">বহুনির্বাচনী</span>
            @endif
            @if($item->has_practical)
              @php($shown=true)
              <span class="badge badge-warning">ব্যবহারিক</span>
            @endif
            @unless($shown)
              <span class="text-muted">—</span>
            @endunless
          </td>
          <td><span class="badge badge-{{ $item->status=='active'?'success':'secondary' }}">{{ $item->status }}</span></td>
          <td class="text-right">
            <a href="{{ route('principal.institute.subjects.edit', [$school,$item]) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
            <form action="{{ route('principal.institute.subjects.destroy', [$school,$item]) }}" method="post" class="d-inline" onsubmit="return confirm('মুছে ফেলবেন?')">@csrf @method('delete')<button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></form>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted">কিছু পাওয়া যায়নি</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center">
    <div>মোট {{ $subjects->total() }}টির মধ্যে {{ $subjects->firstItem() }}–{{ $subjects->lastItem() }}</div>
    <div>{{ $subjects->links() }}</div>
  </div>
</div></div>
@endsection