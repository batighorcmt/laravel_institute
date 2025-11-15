@extends('layouts.admin')
@section('title','ইনস্টিটিউট ব্যবস্থাপনা')
@section('content')
<div class="row mb-3">
  <div class="col-sm-8"><h1 class="m-0">ইনস্টিটিউট</h1></div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width:60px">#</th>
          <th>লোগো</th>
          <th>নাম</th>
          <th>কোড</th>
          <th style="width:140px" class="text-right">অ্যাকশন</th>
        </tr>
      </thead>
      <tbody>
        @forelse($schools as $i => $school)
          <tr>
            <td>{{ $i+1 }}</td>
            <td>
              @if($school->logo)
                <img src="{{ Storage::url($school->logo) }}" alt="logo" style="width:42px;height:42px;object-fit:cover;border-radius:6px;">
              @else
                —
              @endif
            </td>
            <td>{{ $school->name }}</td>
            <td><span class="badge badge-light">{{ $school->code }}</span></td>
            <td class="text-right">
              <a href="{{ route('principal.institute.manage', $school) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-cog mr-1"></i> ম্যানেজ
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted p-4">কোন প্রতিষ্ঠান নির্ধারিত নেই।</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
