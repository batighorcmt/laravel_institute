<div class="print-header d-none d-print-block">
    <div style="text-align:center; border-bottom:1px solid #000; padding:6px 0; font-size:12px;">
        <div style="font-weight:800; font-size:20px; line-height:1.1;">{{ $school->name ?? config('app.name') }}</div>
        @if(!empty($school->address))
            <div style="font-size:12px; margin-top:3px;">{{ $school->address }}</div>
        @endif
        <div style="font-size:14px; font-weight:700; margin-top:6px; text-transform:uppercase;">{{ $reportTitle ?? 'রিপোর্ট' }} @if(!empty($reportType)) ({{ $reportType }}) @endif</div>
        @php
            $meta = [];
            if(!empty($year)) { $meta[] = 'বছর: '.$year; }
            if(!empty($monthName)) { $meta[] = 'মাস: '.$monthName; }
            elseif(!empty($month)) { $meta[] = 'মাস: '.date('F Y', strtotime($month.'-01')); }
            if(!empty($className)) { $meta[] = 'শ্রেণি: '.$className; }
            if(!empty($sectionName)) { $meta[] = 'শাখা: '.$sectionName; }
        @endphp
        @if(!empty($meta))
            <div style="font-size:11px; margin-top:4px;">{{ implode(' | ', $meta) }}</div>
        @endif
        @php
            $bnDigits = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
            $toBn = fn($v)=>strtr((string)$v, $bnDigits);
        @endphp
        <div style="font-size:10px; margin-top:4px;">প্রিন্ট তারিখ: {{ $toBn(now()->format('d-m-Y')) }}</div>
    </div>
    
</div>