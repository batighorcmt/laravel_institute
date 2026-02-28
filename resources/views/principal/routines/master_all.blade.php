@extends('layouts.admin')
@section('title','পূর্ণাঙ্গ মাস্টার রুটিন')

@push('styles')
<style>
@media print {
  body, html, .content-wrapper, .card, .card-body { background:#ffffff !important; }
  .main-header, .main-sidebar, .main-footer, .btn-print, .no-print { display:none !important; }
  .content-wrapper { margin:0; padding:0; }
  @page { size: landscape; margin: 10mm; }
  .page-break-before { page-break-before: always; }
  .print-page { width: 100%; overflow: visible; }
  .table-routine th, .table-routine td { border: 1px solid #000 !important; }
  .flex-container { display: block !important; } 
  .header-info { display: flex !important; margin-bottom: 20px !important; }
}

.table-routine th, .table-routine td { vertical-align: middle; font-size:12px; padding: 4px; border: 1px solid #dee2e6; }
.table-routine thead th { background: #f8f9fa; }
.sub { font-weight:600; display:block; }
.small { font-size: 11px; }
.teacher-col { font-weight: bold; width: 60px; min-width: 60px; text-align: center; }
.flex-container {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}
.flex-container .table-responsive {
    flex: 1;
    overflow-x: auto;
}
.header-info { display:none; align-items:center; justify-content:center; gap:12px; }
@media print {
    .card { border: none !important; box-shadow: none !important; margin-bottom: 0 !important; }
    .card-body { padding: 0 !important; }
}
.header-info .logo { width:64px; height:64px; object-fit:contain; }
.header-info .header-text { text-align:center; margin-top: 10px; }
.school-name { font-size: 28px; font-weight: 800; color:#000; margin-bottom: 2px; }
.routine-title { font-size: 18px; font-weight: 700; color:#000; margin-top:0; }
</style>
@endpush

@section('content')

@php
   $bnMap = ['0'=>'০','1'=>'১ম','2'=>'২য়','3'=>'৩য়','4'=>'৪র্থ','5'=>'৫ম','6'=>'৬ষ্ঠ','7'=>'৭ম','8'=>'৮ম','9'=>'৯ম','10'=>'১০ম'];
   // Calculate chunk sizes:
   $keys = array_keys($activeDays);
   $chunkSize = ceil(count($activeDays) / 2);
   $dayChunks = [];
   if ($chunkSize > 0) {
       $dayChunks[] = array_slice($activeDays, 0, $chunkSize, true);
       $dayChunks[] = array_slice($activeDays, $chunkSize, null, true);
   }
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h1 class="m-0"><i class="fas fa-calendar-alt mr-1"></i> পূর্ণাঙ্গ মাস্টার রুটিন</h1>
  <div>
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print mr-1"></i> প্রিন্ট</button>
  </div>
</div>

<div class="card">
  <div class="card-body">
    
    @if($maxPeriod <= 0 || $teachers->isEmpty())
      <div class="alert alert-warning no-print">কোনো রুটিন পাওয়া যায়নি।</div>
    @else
      <div class="flex-container">
         @foreach($dayChunks as $index => $chunk)
            @if(count($chunk) > 0)
            <div class="print-page table-responsive {{ $index > 0 ? 'page-break-before' : '' }}">
               
               <div class="header-info">
                 @if(!empty($school->logo))
                   <img src="{{ asset('storage/'.$school->logo) }}" alt="{{ $school->name_bn }} logo" class="logo" />
                 @endif
                 <div class="header-text">
                   <h1 class="school-name">{{ $school->name_bn }}</h1>
                   <div class="routine-title">পূর্ণাঙ্গ মাস্টার রুটিন — {{ strtr(date('Y'), ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']) }} (অংশ {{ strtr($index + 1, ['1'=>'১','2'=>'২']) }})</div>
                 </div>
               </div>

               <table class="table table-bordered table-sm table-routine text-center">
                  <thead>
                     <tr>
                        <th rowspan="2" class="teacher-col align-middle">শিক্ষক</th>
                        @foreach($chunk as $dayKey => $dayName)
                           <th colspan="{{ $maxPeriod }}">{{ $dayName }}</th>
                        @endforeach
                     </tr>
                     <tr>
                        @foreach($chunk as $dayKey => $dayName)
                           @for($p=1; $p<=$maxPeriod; $p++)
                              <th>{{ $bnMap[$p] ?? ($p.'ম') }}</th>
                           @endfor
                        @endforeach
                     </tr>
                  </thead>
                  <tbody>
                     @foreach($teachers as $teacher)
                        <tr>
                           <th class="teacher-col align-middle" style="white-space: nowrap;">{{ $teacher->initials ?: mb_substr($teacher->user->name ?? '', 0, 10) }}</th>
                           @foreach($chunk as $dayKey => $dayName)
                              @for($p=1; $p<=$maxPeriod; $p++)
                                 <td>
                                    @php($list = collect($entries[$dayKey.'#'.$teacher->id.'#'.$p] ?? []))
                                    @if($list->isEmpty())
                                       <!-- Empty Data -->
                                    @else
                                       @foreach($list as $e)
                                          <div><span class="sub">{{ $e->class?->name ?? '' }} {{ $e->section ? '- '.$e->section->name : '' }}</span></div>
                                          <div class="small text-muted">{{ $e->subject?->name ?? '' }}</div>
                                          @if($e->room) <div class="small text-muted" style="font-size: 10px; margin-top:2px;">রুম: {{ strtr($e->room, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']) }}</div> @endif
                                          @if(!$loop->last)<hr class="my-1 border-secondary">@endif
                                       @endforeach
                                    @endif
                                 </td>
                              @endfor
                           @endforeach
                        </tr>
                     @endforeach
                  </tbody>
               </table>
            </div>
            @endif
         @endforeach
      </div>
    @endif
  </div>
</div>
@endsection
