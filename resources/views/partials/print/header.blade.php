<div class="print-header d-none d-print-block">
    <div style="text-align:center; border-bottom:1px solid #000; padding:10px 0; font-size:12px; position: relative;">
        @if(!empty($school->logo))
            <div style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">
                <img src="{{ asset('storage/' . $school->logo) }}" style="height: 65px; width: auto; object-fit: contain;">
            </div>
        @endif
        <div style="font-weight:800; font-size:20px; line-height:1.1;">{{ $school->name_bn ?? $school->name ?? config('app.name') }}</div>
        @php
            $address = $school->address_bn ?? $school->address;
        @endphp
        @if(!empty($address))
            <div style="font-size:12px; margin-top:3px;">{{ $address }}</div>
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
    </div>
</div>