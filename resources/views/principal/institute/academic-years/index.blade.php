@extends('layouts.admin')
@section('title','শিক্ষাবর্ষ')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">শিক্ষাবর্ষ - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.academic-years.create',$school) }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> নতুন শিক্ষাবর্ষ</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="card"><div class="card-body p-0">
  <div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead>
        <tr><th>#</th><th>শিক্ষাবর্ষ</th><th>শুরুর তারিখ</th><th>শেষ তারিখ</th><th>স্ট্যাটাস</th><th class="text-right">অ্যাকশন</th></tr>
      </thead>
      <tbody>
        @forelse($years as $i=>$y)
          <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $y->name }}</td>
            <td>{{ $y->start_date->format('d-m-Y') }}</td>
            <td>{{ $y->end_date->format('d-m-Y') }}</td>
            <td>
              @if($y->is_current)
                <span class="badge badge-success">বর্তমান</span>
              @else
                <span class="badge badge-secondary">পূর্ববর্তী</span>
              @endif
            </td>
            <td class="text-right">
              <a href="{{ route('principal.institute.academic-years.edit',[$school,$y]) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
              @if(!$y->is_current)
              <form action="{{ route('principal.institute.academic-years.set-current',[$school,$y]) }}" method="post" class="d-inline">@csrf @method('PATCH')
                <button class="btn btn-warning btn-sm"><i class="fas fa-check mr-1"></i> বর্তমান করুন</button>
              </form>
              <form action="{{ route('principal.institute.academic-years.destroy',[$school,$y]) }}" method="post" class="d-inline" onsubmit="return confirm('মুছে ফেলবেন?')">@csrf @method('DELETE')
                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
              </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted">কোনো শিক্ষাবর্ষ পাওয়া যায়নি</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div></div>
@endsection