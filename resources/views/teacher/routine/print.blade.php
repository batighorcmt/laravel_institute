@extends('layouts.admin')
@section('title','আমার রুটিন প্রিন্ট')

@push('styles')
<style>
@media print {
  body, html, .content-wrapper, .card, .card-body, table, th, td { background:#ffffff !important; }
  .main-header, .main-sidebar, .main-footer, .btn-print, .no-print { display:none !important; }
  .content-wrapper { margin:0; padding:0; }
  @page { size: landscape; margin: 12mm; }
  * { -webkit-print-color-adjust: economy; print-color-adjust: economy; }
  body { color:#000 !important; }
  .text-muted { color:#000 !important; }
  img { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
.table-routine th, .table-routine td { vertical-align: top; font-size:13px; }
.cell-box { margin:0 0 4px 0; padding:0; border:none; background:transparent; }
.cell-box .sub { font-weight:600; display:block; }
.cell-box .cls { color:#000; display:block; }
.header-info { margin-bottom:1rem; display:flex; align-items:center; justify-content:center; gap:12px; }
.header-info .logo { width:64px; height:64px; object-fit:contain; }
.header-info .header-text { text-align:center; }
.school-name { font-size: 32px; font-weight: 800; color:#000; }
.routine-title { font-size: 22px; font-weight: 700; color:#000; margin-top:4px; }
.meta-line { font-size: 16px; font-weight: 600; color:#000; }
.table-bordered th, .table-bordered td { border-color:#000 !important; }
</style>
@endpush

@section('content')

@php
  $bnMap = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
  $teacherName = $teacher->user->name ?? '';
@endphp
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h1 class="m-0"><i class="fas fa-print mr-1"></i> আমার রুটিন প্রিন্ট</h1>
  <div>
    <a href="{{ route('teacher.dashboard') }}" class="btn btn-secondary">ফিরে যান</a>
    <button onclick="window.print()" class="btn btn-primary btn-print"><i class="fas fa-print mr-1"></i> প্রিন্ট</button>
  </div>
</div>
<div class="card">
  <div class="card-body">
    @php
      $activeDays = [];
      foreach($days as $dk=>$dn){
        for($p=1;$p<=$maxPeriod;$p++){
          if(collect($entries[$dk.'#'.$p] ?? [])->isNotEmpty()){
            $activeDays[$dk] = true; break;
          }
        }
      }
      $activePeriods = [];
      for($p=1;$p<=$maxPeriod;$p++){
        foreach($days as $dk=>$dn){
          if(collect($entries[$dk.'#'.$p] ?? [])->isNotEmpty()){
            $activePeriods[$p] = true; break;
          }
        }
      }
    @endphp

    <div class="header-info">
      @if(!empty($school->logo))
        <img src="{{ asset('storage/'.$school->logo) }}" alt="{{ $school->name_bn }} logo" class="logo" />
      @endif
      <div class="header-text">
        <h1 class="school-name mb-1">{{ $school->name_bn }}</h1>
        <div class="routine-title">শিক্ষক রুটিন — {{ strtr(date('Y'), $bnMap ?? []) }}</div>
        <div class="meta-line mt-1">শিক্ষক: {{ $teacherName }} {{ $teacher->designation ? ' - '.$teacher->designation : '' }}</div>
      </div>
    </div>
    
    @if($maxPeriod <= 0 || empty($activeDays) || empty($activePeriods))
      <div class="alert alert-warning">আপনার কোনো ক্লাস নির্ধারিত নেই।</div>
    @else
      <div class="table-responsive">
        <table class="table table-bordered table-sm table-routine text-center">
          <thead>
            <tr>
              <th style="width:110px">দিন / পিরিয়ড</th>
              @for($p=1;$p<=$maxPeriod;$p++)
                @if(isset($activePeriods[$p]))
                  <th>পিরিয়ড {{ strtr($p, $bnMap ?? []) }}</th>
                @endif
              @endfor
            </tr>
          </thead>
          <tbody>
            @foreach($days as $dk=>$dn)
              @if(isset($activeDays[$dk]))
                <tr>
                  <th class="align-middle">{{ $dn }}</th>
                  @for($p=1;$p<=$maxPeriod;$p++)
                    @if(isset($activePeriods[$p]))
                      @php($list = collect($entries[$dk.'#'.$p] ?? []))
                      <td class="align-middle">
                        @forelse($list as $e)
                          <div class="cell-box">
                            <div class="sub">{{ $e->subject?->name ?? '' }}</div>
                            <div class="cls small">{{ $e->class?->name ?? '' }} {{ $e->section ? '- '.$e->section->name : '' }}</div>
                            @if($e->start_time || $e->end_time)
                              <div class="time small text-muted">{{ strtr($e->start_time ?? '', $bnMap ?? []) }}{{ $e->end_time ? ' - '.strtr($e->end_time, $bnMap ?? []) : '' }}</div>
                            @endif
                            @if($e->room)
                              <div class="room small">রুম: {{ strtr($e->room, $bnMap ?? []) }}</div>
                            @endif
                            @if($e->remarks)
                              <div class="remarks small">{{ $e->remarks }}</div>
                            @endif
                          </div>
                        @empty
                          <span class="text-muted">—</span>
                        @endforelse
                      </td>
                    @endif
                  @endfor
                </tr>
              @endif
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection
