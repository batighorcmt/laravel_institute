@extends('layouts.admin')
@section('title','টিম সদস্য তালিকা')
@section('content')
<div class="d-flex justify-content-between mb-3 no-print">
  <h1 class="m-0">টিম সদস্য তালিকা - {{ $team->name }}</h1>
  <div>
    <a href="{{ route('principal.institute.teams.add-students',[$school,$team]) }}" class="btn btn-secondary"><i class="fas fa-user-plus mr-1"></i> সদস্য হালনাগাদ</a>
    <button onclick="window.print()" class="btn btn-primary ml-1"><i class="fas fa-print mr-1"></i> প্রিন্ট</button>
  </div>
</div>
@if(session('success'))<div class="alert alert-success no-print">{{ session('success') }}</div>@endif

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="m-0">বর্তমান সদস্য ({{ $members->count() }})</h5>
    <small class="text-muted d-none d-print-inline">মুদ্রণের সময়: {{ now()->format('d/m/Y H:i') }}</small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-striped table-bordered mb-0">
        <thead>
          <tr>
            <th style="width:60px">#</th>
            <th>নাম</th>
            <th>রোল</th>
            <th>ক্লাস</th>
            <th>শাখা</th>
          </tr>
        </thead>
        <tbody>
          @forelse($members as $i=>$m)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $m->student_name_bn ?? $m->student_name_en }}</td>
              <td>{{ $m->roll_no }}</td>
              <td>{{ $m->class_name }}</td>
              <td>{{ $m->section_name ?? '-' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted">কোনো সদস্য নেই</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer text-center d-print-none">
    <small class="text-muted">মোট: {{ $members->count() }} শিক্ষার্থী</small>
  </div>
</div>

<style>
@media print {
  .no-print, .no-print * { display: none !important; }
  body { background: #fff; }
  table { font-size: 12px; }
  th, td { padding: 4px !important; }
}
</style>
@endsection