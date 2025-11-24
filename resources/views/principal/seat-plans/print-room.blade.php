@extends('layouts.print')

@php
    $lang = request('lang', 'bn');
    $printTitle = $lang === 'bn' ? 'সীট প্ল্যান' : 'Seat Plan';
    $printSubtitle = $seatPlan->name;
    
    // Bengali number conversion helper
    function toBengaliNumber($number) {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace($en, $bn, $number);
    }
@endphp

@push('print_head')
<style>
    @page { size: A4; margin: 8mm; }
    .shift-overlay{ position:absolute; top:0; right:0; border:2px solid #333; padding:6px 10px; font-weight:800; background: rgba(255,247,168,0.95); color:#000; border-radius:6px; line-height:1.05; text-align:center; z-index: 20; }
    .shift-overlay .line1{ font-size: 13px; }
    .shift-overlay .line2{ font-size: 16px; }
    .room-number { font-weight: 800; font-size: 22px; text-align: center; margin: 0 0 10px; padding-bottom: 6px; border-bottom: 2px solid #333; }
    .seat-area { display: flex; gap: 12px; align-items: flex-start; justify-content: center; flex-wrap: nowrap; }
    .column { flex: 0 0 33.333%; min-width: 0; }
    .col-title { text-align: center; font-weight: 700; margin-bottom: 6px; }
    .bench { border: 1px dashed #bbb; padding: 8px; margin-bottom: 8px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
    .seat { width: 48%; padding: 6px 6px; font-size: 13px; min-height: 40px; text-align: center;
        display: grid; align-items: center; column-gap: 0; row-gap: 2px;
        grid-template-columns: auto 1fr;
        grid-template-areas: "img roll" "name name" "class class";
    }
    .seat.right{
        grid-template-columns: 1fr auto;
        grid-template-areas: "roll img" "name name" "class class";
    }
    .seat img { grid-area: img; width: 36px; height: 36px; border-radius: 4px; object-fit: cover; position: static; }
    .seat .roll { grid-area: roll; font-size: 24px; font-weight: 900; color: #b00; line-height:1; }
    .seat .name { grid-area: name; font-size: 12px; font-weight: 600; line-height:1.1; }
    .seat .class { grid-area: class; font-size: 16px; color: #333; line-height:1.1; }
    .stats { margin-top: 14px; border-top: 1px solid #eee; padding-top: 10px; font-size: 13px; }
    .stats h5{ margin:6px 0; }
    .stats ul{ margin:0; padding-left:18px; }
    
    .seat.grade-10 .roll, .seat.grade-10 .name, .seat.grade-10 .class { color: #0a8a0a; }
    .seat.grade-9 .roll, .seat.grade-9 .name, .seat.grade-9 .class { color: #0b2e7a; }
    .seat.grade-8 .roll, .seat.grade-8 .name, .seat.grade-8 .class { color: #c40000; }
    .seat.grade-7 .roll, .seat.grade-7 .name, .seat.grade-7 .class { color: #800000; }
    .seat.grade-6 .roll, .seat.grade-6 .name, .seat.grade-6 .class { color: #000000; }

    .stats-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .stat-col{ border:1px solid #e6e6e6; border-radius:6px; padding:8px; }
    .badge{ display:inline-block; padding:2px 6px; border-radius:4px; font-weight:700; }
    .badge.grade-10{ color:#0a8a0a; border:1px solid #0a8a0a; }
    .badge.grade-9{ color:#0b2e7a; border:1px solid #0b2e7a; }
    .badge.grade-8{ color:#c40000; border:1px solid #c40000; }
    .badge.grade-7{ color:#800000; border:1px solid #800000; }
    .badge.grade-6{ color:#000; border:1px solid #000; }
    
    @media print {
        .seat-area{ display: grid !important; grid-template-columns: repeat(3, minmax(0, 1fr)); column-gap: 8mm; }
        .column{ width:auto !important; min-width: 0 !important; }
        .bench{ break-inside: avoid; page-break-inside: avoid; }
    }
    @media (max-width: 800px) { 
        .seat-area { flex-direction: column; } 
        .column { min-width: auto; } 
    }
</style>
@endpush

@section('print_header_right')
    @php
        $shiftName = (string)($seatPlan->shift ?? 'Morning');
        $sn = strtolower(trim($shiftName));
        if (strpos($sn,'even') !== false) { $shiftLabel = $lang === 'bn' ? 'সন্ধ্যা শিফট' : 'Evening Shift'; }
        elseif (strpos($sn,'morn') !== false) { $shiftLabel = $lang === 'bn' ? 'সকাল শিফট' : 'Morning Shift'; }
        else { $shiftLabel = ucwords($shiftName).' '.($lang === 'bn' ? 'শিফট' : 'Shift'); }
        $shiftParts = preg_split('/\s+/', trim($shiftLabel), 2);
        $shiftLine1 = $shiftParts[0] ?? $shiftLabel;
        $shiftLine2 = $shiftParts[1] ?? '';
    @endphp
    <div class="shift-overlay">
        <div class="line2">{{ $shiftLine1 }}</div>
        <div class="line1">{{ $shiftLine2 }}</div>
    </div>
@endsection

@section('content')

    @php
        function detectGradeFromClass($className){
            $c = trim((string)$className);
            if ($c==='') return null;
            $lc = strtolower($c);
            if (strpos($lc,'six')!==false) return 6;
            if (strpos($lc,'seven')!==false) return 7;
            if (strpos($lc,'eight')!==false) return 8;
            if (strpos($lc,'nine')!==false) return 9;
            if (strpos($lc,'ten')!==false) return 10;
            if (preg_match('/\b(6)\b/', $c)) return 6;
            if (preg_match('/\b(7)\b/', $c)) return 7;
            if (preg_match('/\b(8)\b/', $c)) return 8;
            if (preg_match('/\b(9)\b/', $c)) return 9;
            if (preg_match('/\b(10)\b/', $c)) return 10;
            return null;
        }
        
        function badgeClassFor($className){ 
            $g = detectGradeFromClass($className); 
            return $g ? ('grade-'.(int)$g) : ''; 
        }
    @endphp

    <div class="room-number">
        {{ $lang === 'bn' ? 'রুম নং:' : 'Room No:' }} {{ $lang === 'bn' ? toBengaliNumber($room->room_no) : $room->room_no }}
    </div>

    <div class="seat-area">
        @php 
            $colNames = $lang === 'bn' 
                ? [1=>'বাম কলাম', 2=>'মধ্য কলাম', 3=>'ডান কলাম']
                : [1=>'Left Column', 2=>'Middle Column', 3=>'Right Column'];
        @endphp
        @for($col = 1; $col <= $room->columns_count; $col++)
            @php
                $benches = $col == 1 ? $room->col1_benches : ($col == 2 ? $room->col2_benches : $room->col3_benches);
            @endphp
            
            <div class="column">
                <div class="col-title">{{ $colNames[$col] }}</div>
                
                @for($bench = 1; $bench <= $benches; $bench++)
                    @php
                        $leftAllocation = $room->allocations->where('col_no', $col)->where('bench_no', $bench)->where('position', 'Left')->first();
                        $rightAllocation = $room->allocations->where('col_no', $col)->where('bench_no', $bench)->where('position', 'Right')->first();
                    @endphp
                    
                    <div class="bench">
                        <!-- Left Seat -->
                        @php
                            $leftGrade = $leftAllocation && $leftAllocation->student && $leftAllocation->student->class 
                                ? detectGradeFromClass($leftAllocation->student->class->name) 
                                : null;
                            $leftGradeClass = $leftGrade ? ' grade-'.$leftGrade : '';
                        @endphp
                        <div class="seat left{{ $leftGradeClass }}">
                            @if($leftAllocation && $leftAllocation->student)
                                @if($leftAllocation->student->photo)
                                    <img src="{{ asset('storage/students/' . $leftAllocation->student->photo) }}" alt="photo">
                                @endif
                                @php
                                    $rollDisplay = $leftAllocation->student->roll ?? $leftAllocation->student->student_id;
                                    if($lang === 'bn') $rollDisplay = toBengaliNumber($rollDisplay);
                                @endphp
                                <div class="roll">{{ $rollDisplay }}</div>
                                <div class="name">{{ Str::limit($leftAllocation->student->student_name_en, 15) }}</div>
                                <div class="class">{{ $leftAllocation->student->class->name ?? '' }}</div>
                            @else
                                --
                            @endif
                        </div>
                        
                        <!-- Right Seat -->
                        @php
                            $rightGrade = $rightAllocation && $rightAllocation->student && $rightAllocation->student->class 
                                ? detectGradeFromClass($rightAllocation->student->class->name) 
                                : null;
                            $rightGradeClass = $rightGrade ? ' grade-'.$rightGrade : '';
                        @endphp
                        <div class="seat right{{ $rightGradeClass }}">
                            @if($rightAllocation && $rightAllocation->student)
                                @if($rightAllocation->student->photo)
                                    <img src="{{ asset('storage/students/' . $rightAllocation->student->photo) }}" alt="photo">
                                @endif
                                @php
                                    $rollDisplay = $rightAllocation->student->roll ?? $rightAllocation->student->student_id;
                                    if($lang === 'bn') $rollDisplay = toBengaliNumber($rollDisplay);
                                @endphp
                                <div class="roll">{{ $rollDisplay }}</div>
                                <div class="name">{{ Str::limit($rightAllocation->student->student_name_en, 15) }}</div>
                                <div class="class">{{ $rightAllocation->student->class->name ?? '' }}</div>
                            @else
                                --
                            @endif
                        </div>
                    </div>
                @endfor
            </div>
        @endfor
    </div>

    @php
        $classCounts = [];
        $totalAssigned = 0;
        foreach($room->allocations as $allocation){
            if($allocation->student && $allocation->student->class){
                $className = $allocation->student->class->name;
                $classCounts[$className] = ($classCounts[$className] ?? 0) + 1;
                $totalAssigned++;
            }
        }
    @endphp

    <div class="stats">
        <div class="stats-grid">
            <div class="stat-col">
                <h5>{{ $lang === 'bn' ? 'শ্রেণিভিত্তিক পরিসংখ্যান' : 'Class-wise statistics' }}</h5>
                <div>
                    <strong>{{ $lang === 'bn' ? 'মোট শিক্ষার্থী — ' : 'Total students — ' }}</strong> 
                    {{ $lang === 'bn' ? toBengaliNumber($totalAssigned) : $totalAssigned }}
                </div>
                @if(!empty($classCounts))
                    <ul>
                        @foreach($classCounts as $cn => $cnt)
                            @php $bc = badgeClassFor($cn); @endphp
                            <li>
                                <span class="badge {{ $bc }}">{{ $cn }}</span> — 
                                {{ $lang === 'bn' ? toBengaliNumber($cnt) : $cnt }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="stat-col">
                <h5>{{ $lang === 'bn' ? 'গ্রুপভিত্তিক (৯-১০)' : 'Group-wise (9-10)' }}</h5>
                <div style="color:#666;">{{ $lang === 'bn' ? 'প্রযোজ্য নয়' : 'Not applicable' }}</div>
            </div>
            <div class="stat-col">
                <h5>{{ $lang === 'bn' ? 'ঐচ্ছিক বিষয় (৯-১০)' : 'Optional subjects (9-10)' }}</h5>
                <div style="color:#666;">{{ $lang === 'bn' ? 'প্রযোজ্য নয়' : 'Not applicable' }}</div>
            </div>
        </div>
    </div>

@endsection
