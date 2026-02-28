@extends('layouts.admin')
@section('title','মাস্টার রুটিন প্রিন্ট')

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
.table-routine th, .table-routine td { vertical-align: top; font-size:12px; }
.cell-box { margin:0; padding:0; border:none; background:transparent; }
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
@endphp
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h1 class="m-0"><i class="fas fa-print mr-1"></i> মাস্টার রুটিন প্রিন্ট</h1>
  <div>
    <a href="{{ route('principal.institute.routine.master', ['school' => $school->id, 'day_of_week' => $selectedDay]) }}" class="btn btn-secondary">ফিরে যান</a>
    <button onclick="window.print()" class="btn btn-primary btn-print"><i class="fas fa-print mr-1"></i> প্রিন্ট</button>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <div class="header-info">
      @if(!empty($school->logo))
        <img src="{{ asset('storage/'.$school->logo) }}" alt="{{ $school->name_bn }} logo" class="logo" />
      @endif
      <div class="header-text">
        <h1 class="school-name mb-1">{{ $school->name_bn }}</h1>
        <div class="routine-title">স্কুল রুটিন (মাস্টার) — {{ strtr(date('Y'), $bnMap ?? []) }}</div>
        <div class="meta-line mt-1">দিন: {{ $days[$selectedDay] }}</div>
      </div>
    </div>
    
    @if($maxPeriod <= 0 || $entries->isEmpty())
      <div class="alert alert-warning">এই দিনে কোনো ক্লাস নির্ধারিত নেই।</div>
    @else
      <div class="table-responsive">
        <table class="table table-bordered table-sm table-routine text-center">
          <thead>
            <tr>
              <th style="width:180px">শিক্ষক</th>
              @for($p=1; $p<=$maxPeriod; $p++)
                <th>পিরিয়ড {{ strtr($p, $bnMap ?? []) }}</th>
              @endfor
            </tr>
          </thead>
          <tbody>
            @foreach($teachers as $teacher)
              {{-- Only print teachers who actually have classes assigned --}}
              @php
                $hasClasses = false;
                for($p=1; $p<=$maxPeriod; $p++){
                    if(!empty($entries[$teacher->id.'#'.$p])) {
                        $hasClasses = true;
                        break;
                    }
                }
              @endphp

              @if($hasClasses)
              <tr>
                <th class="align-middle text-left">
                  {{ $teacher->user->name ?? 'Unknown' }}
                  @if($teacher->initials) <br><small class="text-muted">({{ $teacher->initials }})</small> @endif
                </th>
                @for($p=1; $p<=$maxPeriod; $p++)
                  @php($list = collect($entries[$teacher->id.'#'.$p] ?? []))
                  <td class="align-middle">
                    @forelse($list as $e)
                      <div class="cell-box">
                        <div class="sub">{{ $e->subject?->name ?? '' }}</div>
                        <div class="cls small">{{ $e->class?->name ?? '' }} {{ $e->section ? '- '.$e->section->name : '' }}</div>
                        @if($e->room)
                          <div class="room small">রুম: {{ strtr($e->room, $bnMap ?? []) }}</div>
                        @endif
                      </div>
                      @if(!$loop->last) <hr class="my-1 border-dark"> @endif
                    @empty
                      <span class="text-muted">—</span>
                    @endforelse
                  </td>
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
