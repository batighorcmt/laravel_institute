@extends('layouts.print')
@section('title','ক্লাস রুটিন প্রিন্ট')

@php
  $lang = request('lang','bn');
  $isEn = $lang === 'en';
  $teacherName = $section->classTeacher?->user?->name ?? ($section->class_teacher_name ?? '');
  $className = $isEn ? $class->name : ($class->bangla_name ?: $class->name);
  $sectionName = $isEn ? $section->name : ($section->bangla_name ?: $section->name);

  $bnMapTitle = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
  $yearStr = $isEn ? date('Y') : strtr(date('Y'), $bnMapTitle);
  $printTitle = ($isEn ? 'Class Routine — ' : 'ক্লাস রুটিন — ') . $yearStr;
  $printSubtitle = $isEn
      ? ('Class: '.$className.' | Section: '.$sectionName.($teacherName ? ' | Class Teacher: '.$teacherName : ''))
      : ('শ্রেণি: '.$className.' | শাখা: '.$sectionName.($teacherName ? ' | শ্রেণি শিক্ষক: '.$teacherName : ''));
@endphp

@push('print_head')
<style>
@page { size: auto; margin: 12mm; }
.table-routine th, .table-routine td { vertical-align: middle; font-size:13px; text-align:center; }
.period-time { font-size:11px; font-weight:400; color:#333; margin-top:2px; }
.cell-box { margin:0 auto; padding:0; border:none; background:transparent; }
.cell-box .sub { font-weight:700; font-size:15px; display:block; margin-bottom:3px; }
.cell-box .teach { color:#000; display:block; }
.table-bordered th, .table-bordered td { border:1px solid #000 !important; }
table { width:100%; border-collapse:collapse; }
thead th { background:#f0f0f0; font-weight:700; text-align:center; vertical-align:middle; }
tbody th { text-align:center; vertical-align:middle; }
td { text-align:center; vertical-align:middle; padding:4px 6px; }
</style>
@endpush

@section('content')
@php
  $bnMap = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

  // AM/PM formatter — request #5. Bangla numerals + পূর্বাহ্ণ/অপরাহ্ণ for bn.
  $fmtTime = function($t) use ($isEn, $bnMap) {
      if (!$t) return '';
      try {
          $formatted = \Carbon\Carbon::parse($t)->format('h:i A');
      } catch (\Throwable $e) {
          return $t;
      }
      if ($isEn) return $formatted;
      $formatted = strtr($formatted, $bnMap);
      return strtr($formatted, ['AM' => 'এম', 'PM' => 'পিএম']);
  };

  $labels = $isEn ? [
      'day_period' => 'Day / Period', 'period' => 'Period',
      'no_period' => 'Period count not set for this class-section.',
      'no_class' => 'No classes scheduled for this class.',
      'room' => 'Room',
  ] : [
      'day_period' => 'দিন / পিরিয়ড', 'period' => 'পিরিয়ড',
      'no_period' => 'এই শ্রেণি-শাখার পিরিয়ড সংখ্যা নির্ধারণ করা হয়নি।',
      'no_class' => 'এই শ্রেণির জন্য কোনো ক্লাস নির্ধারিত নেই।',
      'room' => 'রুম',
  ];

  $activeDays = [];
  foreach($days as $dk=>$dn){
    for($p=1;$p<=$periodCount;$p++){
      if(collect($entries[$dk.'#'.$p] ?? [])->isNotEmpty()){
        $activeDays[$dk] = true; break;
      }
    }
  }
  $activePeriods = [];
  $periodTimes = [];
  for($p=1;$p<=$periodCount;$p++){
    foreach($days as $dk=>$dn){
      $list = collect($entries[$dk.'#'.$p] ?? []);
      if($list->isNotEmpty()){
        $activePeriods[$p] = true;
        if(!isset($periodTimes[$p])){
          $withTime = $list->first(fn($e)=>$e->start_time || $e->end_time);
          if($withTime) $periodTimes[$p] = [$withTime->start_time, $withTime->end_time];
        }
      }
    }
  }
@endphp

@if($periodCount <= 0)
  <div style="padding:16px;text-align:center;border:1px solid #ccc;margin-top:12px;">{{ $labels['no_period'] }}</div>
@elseif(empty($activeDays) || empty($activePeriods))
  <div style="padding:16px;text-align:center;border:1px solid #ccc;margin-top:12px;">{{ $labels['no_class'] }}</div>
@else
  <div style="overflow-x:auto;">
    <table class="table-bordered table-routine">
      <thead>
        <tr>
          <th style="width:110px">{{ $labels['day_period'] }}</th>
          @for($p=1;$p<=$periodCount;$p++)
            @if(isset($activePeriods[$p]))
              <th>
                {{ $labels['period'] }} {{ $isEn ? $p : strtr((string)$p, $bnMap) }}
                @if(isset($periodTimes[$p]))
                  <div class="period-time">{{ $fmtTime($periodTimes[$p][0]) }}{{ $periodTimes[$p][1] ? ' - '.$fmtTime($periodTimes[$p][1]) : '' }}</div>
                @endif
              </th>
            @endif
          @endfor
        </tr>
      </thead>
      <tbody>
        @foreach($days as $dk=>$dn)
          @if(isset($activeDays[$dk]))
            <tr>
              <th>{{ $dn }}</th>
              @for($p=1;$p<=$periodCount;$p++)
                @if(isset($activePeriods[$p]))
                  @php($list = collect($entries[$dk.'#'.$p] ?? []))
                  <td>
                    @forelse($list as $e)
                      <div class="cell-box">
                        <span class="sub">{{ $isEn ? $e->subject?->name : ($e->subject?->bangla_name ?: $e->subject?->name) }}</span>
                        <span class="teach">{{ $e->teacher?->user?->name }}</span>
                        @if($e->room)
                          <span class="room" style="font-size:11px;color:#333;display:block;">{{ $labels['room'] }}: {{ $isEn ? $e->room : strtr($e->room, $bnMap) }}</span>
                        @endif
                        @if($e->remarks)
                          <span class="remarks" style="font-size:11px;color:#333;display:block;">{{ $e->remarks }}</span>
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
