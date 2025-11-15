@extends('layouts.admin')
@section('title','রুটিন প্রিন্ট')

@push('styles')
<style>
@media print {
  /* Clean, ink-friendly print: remove UI chrome, force white backgrounds */
  body, html, .content-wrapper, .card, .card-body, table, th, td { background:#ffffff !important; }
  .main-header, .main-sidebar, .main-footer, .btn-print, .no-print { display:none !important; }
  .content-wrapper { margin:0; padding:0; }
  @page { size: auto; margin: 12mm; }
  /* Avoid preserving decorative colors so browsers can skip background graphics */
  * { -webkit-print-color-adjust: economy; print-color-adjust: economy; }
  body { color:#000 !important; }
  .text-muted { color:#000 !important; }
}
.table-routine th, .table-routine td { vertical-align: top; font-size:13px; }
/* Simplify each entry: remove individual box borders/backgrounds */
.cell-box { margin:0 0 4px 0; padding:0; border:none; background:transparent; }
.cell-box .sub { font-weight:600; display:block; }
.cell-box .teach { color:#000; display:block; }
.header-info { margin-bottom:1rem; }
.school-name { font-size: 32px; font-weight: 800; color:#000; }
.routine-title { font-size: 22px; font-weight: 700; color:#000; margin-top:4px; }
.meta-line { font-size: 14px; color:#000; }
.table-bordered th, .table-bordered td { border-color:#000 !important; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h1 class="m-0"><i class="fas fa-print mr-1"></i> রুটিন প্রিন্ট</h1>
  <div>
    <a href="{{ route('principal.institute.routine.panel', $school) }}" class="btn btn-secondary">ফিরে যান</a>
    <button onclick="window.print()" class="btn btn-primary btn-print"><i class="fas fa-print mr-1"></i> প্রিন্ট</button>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <div class="header-info text-center">
      <h1 class="school-name mb-1">{{ $school->name }}</h1>
      <div class="routine-title">ক্লাস রুটিন — {{ date('Y') }}</div>
      <div class="meta-line">শ্রেণি: {{ $class->name }} | শাখা: {{ $section->name }}</div>
    </div>
    @if($periodCount <= 0)
      <div class="alert alert-warning">এই শ্রেণি-শাখার পিরিয়ড সংখ্যা নির্ধারণ করা হয়নি।</div>
    @else
    <div class="table-responsive">
      <table class="table table-bordered table-sm table-routine">
        <thead>
          <tr>
            <th style="width:110px">পিরিয়ড\\দিন</th>
            @foreach($days as $dk=>$dn)
              <th>{{ $dn }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @for($p=1;$p<=$periodCount;$p++)
            <tr>
              <th>পিরিয়ড {{ $p }}</th>
              @foreach($days as $dk=>$dn)
                @php($list = $entries[$dk.'#'.$p] ?? collect())
                <td>
                  @forelse($list as $e)
                    <div class="cell-box">
                      <div class="sub">{{ $e->subject?->name }}</div>
                      <div class="teach">{{ $e->teacher?->name }}</div>
                      @if($e->start_time || $e->end_time)
                        <div class="time small text-muted">{{ $e->start_time }}{{ $e->end_time ? ' - '.$e->end_time : '' }}</div>
                      @endif
                      @if($e->room)
                        <div class="room small">রুম: {{ $e->room }}</div>
                      @endif
                      @if($e->remarks)
                        <div class="remarks small">{{ $e->remarks }}</div>
                      @endif
                    </div>
                  @empty
                    <span class="text-muted">—</span>
                  @endforelse
                </td>
              @endforeach
            </tr>
          @endfor
        </tbody>
      </table>
    </div>
    @endif
  </div>
</div>
@endsection