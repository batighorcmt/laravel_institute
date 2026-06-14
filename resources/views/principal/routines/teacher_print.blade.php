@extends('layouts.print')
@section('title','শিক্ষক রুটিন প্রিন্ট')

@php
  $printTitle    = 'শিক্ষক রুটিন — ' . date('Y');
  $printSubtitle = 'শিক্ষক: ' . ($teacher->user->name ?? '') . ($teacher->initials ? ' - '.$teacher->initials : '');
@endphp

@push('print_head')
<style>
@page { size: landscape; margin: 12mm; }
.table-routine th, .table-routine td { vertical-align: top; font-size:13px; padding:4px 6px; }
.cell-box { margin:0 0 4px 0; padding:0; }
.cell-box .sub { font-weight:700; display:block; }
.cell-box .cls { display:block; font-size:12px; }
.cell-box .time, .cell-box .room, .cell-box .remarks { font-size:11px; color:#333; display:block; }
.table-bordered th, .table-bordered td { border:1px solid #000 !important; }
table { width:100%; border-collapse:collapse; }
thead th { background:#f0f0f0; font-weight:700; text-align:center; }
tbody th { text-align:center; }
td { text-align:center; }
@media print {
  @page { size: landscape; margin: 12mm; }
  * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
@endpush

@section('content')
@php
  $bnMap = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

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

@if($maxPeriod <= 0 || empty($activeDays) || empty($activePeriods))
  <div style="padding:16px;text-align:center;border:1px solid #ccc;margin-top:12px;">
    এই শিক্ষকের জন্য কোনো ক্লাস নির্ধারিত নেই।
  </div>
@else
  <div style="overflow-x:auto;">
    <table class="table-bordered table-routine">
      <thead>
        <tr>
          <th style="width:110px">দিন / পিরিয়ড</th>
          @for($p=1;$p<=$maxPeriod;$p++)
            @if(isset($activePeriods[$p]))
              <th>পিরিয়ড {{ strtr((string)$p, $bnMap) }}</th>
            @endif
          @endfor
        </tr>
      </thead>
      <tbody>
        @foreach($days as $dk=>$dn)
          @if(isset($activeDays[$dk]))
            <tr>
              <th>{{ $dn }}</th>
              @for($p=1;$p<=$maxPeriod;$p++)
                @if(isset($activePeriods[$p]))
                  @php($list = collect($entries[$dk.'#'.$p] ?? []))
                  <td>
                    @forelse($list as $e)
                      <div class="cell-box">
                        <span class="sub">{{ $e->subject?->name ?? '' }}</span>
                        <span class="cls">{{ $e->class?->name ?? '' }}{{ $e->section ? ' - '.$e->section->name : '' }}</span>
                        @if($e->start_time || $e->end_time)
                          <span class="time">{{ strtr($e->start_time ?? '', $bnMap) }}{{ $e->end_time ? ' - '.strtr($e->end_time, $bnMap) : '' }}</span>
                        @endif
                        @if($e->room)
                          <span class="room">রুম: {{ strtr($e->room, $bnMap) }}</span>
                        @endif
                        @if($e->remarks)
                          <span class="remarks">{{ $e->remarks }}</span>
                        @endif
                      </div>
                    @empty
                      <span style="color:#999">—</span>
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
@endsection
