@extends('layouts.print')
@section('title','Seat Plan - Room '.$room->room_no)
@php
    // Helper to convert digits to Bangla
    $bnDigits = function($input){
        $map = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
        return strtr((string)$input,$map);
    };
    // Determine only columns that have benches >0
    $renderColumns = [];
    for($i=1;$i<= (int)$room->columns_count;$i++){
        $bf = 'col'.$i.'_benches';
        if((int)$room->$bf > 0){ $renderColumns[] = $i; }
    }
    $effectiveCount = count($renderColumns);
    if($effectiveCount === 0){ $effectiveCount = 1; }
    // Language aware header variables
    $lang = request('lang','bn');
    $printTitle = $lang==='bn' ? ($seatPlan->name.' সীট প্ল্যান') : ($seatPlan->name.' Seat Plan');
    // Build subtitle with room number; convert digits if Bangla
    $roomNoDisplay = $lang==='bn' ? $bnDigits($room->room_no) : $room->room_no;
    $printSubtitle = ($lang==='bn' ? 'রুম নং: ' : 'Room No: ').$roomNoDisplay.( $room->title ? ' ('.$room->title.')' : '' );
    $school = $seatPlan->school; // provide to layout for logo/name
@endphp
<style>
    @media print { @page{size:A4; margin:10mm;} .no-print{display:none!important;} body{margin:0;} .seat-area{display:grid!important;grid-template-columns:repeat({{ $effectiveCount }},1fr);column-gap:8mm;} .bench{break-inside:avoid;page-break-inside:avoid;} }
    body{font-family: var(--bs-body-font-family,'Kalpurush'), 'Kalpurush','Arial',sans-serif;margin:4px 0 0;color:#000;}
    /* Local seat grid styles (header handled by layout) */
    .seat-area{display:grid;grid-template-columns:repeat({{ $effectiveCount }},1fr);gap:10px;align-items:flex-start;}
    /* Unified sizing so width expansion does not inflate fonts/photos */
    .seat-box{min-height:80px;}
    .seat-roll{font-size:32px;}
    .seat-name{font-size:12px;}
    .seat-photo{width:40px;height:40px;}
    /* Ensure grid template columns match photo width to prevent overflow */
    .seat-box{grid-template-columns:40px 1fr;}
    .seat-box.right{grid-template-columns:1fr 40px;}
    .seat-column{width:100%;}
    .col-title{text-align:center;font-weight:700;margin-bottom:6px;}
    .bench{border:1px dashed #999;padding:6px;margin:6px 0;border-radius:6px;display:flex;justify-content:space-between;align-items:stretch;gap:8px;}
    .seat-box{flex:1 1 50%;padding:6px 6px;font-size:11px;min-height:70px;text-align:center;display:grid;grid-template-areas:"photo roll" "name name";grid-template-columns:40px 1fr;align-items:center;row-gap:2px;border:1px solid #ccc;border-radius:6px;background:#f9f9f9;}
    .seat-box.right{grid-template-areas:"roll photo" "name name";grid-template-columns:1fr 40px;}
    .seat-box.assigned{background:#eefaf1;border-color:#198754;}
    .seat-roll{grid-area:roll;font-size:28px;font-weight:800;color:#b00;line-height:1;letter-spacing:1px;}
    .seat-name{grid-area:name;font-size:12px;font-weight:600;line-height:1.1;}
    .seat-photo{grid-area:photo;width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid #999;background:#fff;}
    .empty .seat-roll{color:#777;}
    /* Remove local footer note (global footer already exists) */
    .footer-note{display:none;}
    /* Larger styled room number inside header subtitle */
    .page-subtitle{font-size:26px !important; font-weight:800;}
    .page-subtitle{display:inline-block;}
    .page-subtitle{padding:6px 18px; border:2px solid #111; border-radius:6px; background:#fff;}
    @media print { .page-subtitle{background:#fff;} }
    .content-wrapper, .wrapper > .content-wrapper { background:#fff; }
    @media print { .main-header, .main-sidebar, .no-print, .nav, .btn, .footer-note-print-hide { display:none !important; } body, .content-wrapper{margin:0!important;padding:0!important;} }
 </style>
@section('content')
<div class="no-print" style="text-align:right;margin-bottom:8px;">
    <span style="margin-left:10px;font-size:12px;font-weight:600;">{{ $lang==='bn' ? 'পরীক্ষা:' : 'Exam:' }} {{ $lang==='bn' ? ($seatPlan->exam->name_bn ?? $seatPlan->exam->name) : ($seatPlan->exam->name ?? $seatPlan->exam->name_bn) }} | {{ $lang==='bn' ? 'প্ল্যান:' : 'Plan:' }} {{ $seatPlan->name }}</span>
</div>
<div class="seat-area cols-{{ $effectiveCount }}">
    @foreach($renderColumns as $c)
        @php $benchField = 'col'.$c.'_benches'; $benches = (int) $room->$benchField; @endphp
        <div class="seat-column">
            <div class="col-title">@switch($c) @case(1) {{ $lang==='bn' ? 'বাম সারি' : 'Left Column' }} @break @case(2) {{ $lang==='bn' ? 'মধ্য সারি' : 'Middle Column' }} @break @default {{ $lang==='bn' ? 'ডান সারি' : 'Right Column' }} @endswitch</div>
            @foreach(range(1,$benches) as $b)
                <div class="bench">
                    @foreach(['L','R'] as $pos)
                        @php $existing = $room->allocations->where('col_no',$c)->where('bench_no',$b)->where('position',$pos)->first();
                             $app = $existing ? ($appMap[$existing->application_id] ?? null) : null;
                             $boxClass = $pos==='R' ? 'right' : 'left'; @endphp
                        <div class="seat-box {{ $boxClass }} {{ ($existing && $app)?'assigned':'empty' }}">
                            @php
                                $photoUrl = asset('images/default-avatar.svg');
                                if($existing && $app && $app->photo){
                                    $candidates = [
                                        'uploads/students/'.$app->photo,
                                        'uploads/students/photos/'.$app->photo,
                                        'storage/students/'.$app->photo,
                                        'storage/'.$app->photo,
                                        'storage/admission/'.$app->photo,
                                        'storage/admission/photos/'.$app->photo,
                                    ];
                                    foreach($candidates as $cand){
                                        if(file_exists(public_path($cand))){
                                            $photoUrl = asset($cand); break;
                                        }
                                    }
                                    if($photoUrl===asset('images/default-avatar.svg')){
                                        if(\Illuminate\Support\Facades\Storage::disk('public')->exists($app->photo)){
                                            $photoUrl = asset('storage/'.$app->photo);
                                        }
                                    }
                                }
                            @endphp
                            <img src="{{ $photoUrl }}" alt="photo" class="seat-photo">
                            @php
                                $rollDisplay = ($existing && $app) ? ($lang==='bn' ? $bnDigits($app->admission_roll_no) : $app->admission_roll_no) : '—';
                                $nameDisplay = ($existing && $app) ? ($lang==='bn' ? ($app->name_bn ?? $app->name_en) : ($app->name_en ?? $app->name_bn)) : ($lang==='bn' ? 'খালি' : 'Empty');
                            @endphp
                            <div class="seat-roll">{{ $rollDisplay }}</div>
                            <div class="seat-name">{{ $nameDisplay }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@php
    // Room statistics (assigned seats by gender)
    $totalAssigned = 0; $maleCount = 0; $femaleCount = 0;
    foreach($room->allocations as $al){
        $app = $appMap[$al->application_id] ?? null;
        if(!$app) continue; $totalAssigned++;
        $g = strtolower(trim($app->gender ?? ''));
        if(in_array($g,['male','m','ছেলে'])) $maleCount++;
        elseif(in_array($g,['female','f','মেয়ে','মেয়','মেয়ے'])) $femaleCount++;
    }
    $totalDisp = $lang==='bn' ? $bnDigits($totalAssigned) : $totalAssigned;
    $maleDisp = $lang==='bn' ? $bnDigits($maleCount) : $maleCount;
    $femaleDisp = $lang==='bn' ? $bnDigits($femaleCount) : $femaleCount;
@endphp
<div style="margin-top:18px;font-weight:700;font-size:14px;text-align:center;border-top:1px dashed #999;padding-top:8px;">
    {{ $lang==='bn' ? 'রুমে মোট:' : 'Total:' }} {{ $totalDisp }}
    &nbsp;|&nbsp; {{ $lang==='bn' ? 'ছেলে:' : 'Boys:' }} {{ $maleDisp }}
    &nbsp;|&nbsp; {{ $lang==='bn' ? 'মেয়ে:' : 'Girls:' }} {{ $femaleDisp }}
</div>
{{-- Local printed time removed; global footer handles it --}}
@endsection