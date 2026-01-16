<!DOCTYPE html>
<?php $lang = request('lang','bn'); ?>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title', (isset($printTitle)?$printTitle:'Print'))</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Use same font stack as admin dashboard (Kalpurush primary) for both languages -->
    <style>
        @font-face { font-family:'Kalpurush'; font-weight:400; font-display:swap; src: url('/fonts/kalpurush/kalpurush.woff2') format('woff2'), url('/fonts/kalpurush/kalpurush.ttf') format('truetype'); }
        @font-face { font-family:'Kalpurush'; font-weight:600; font-display:swap; src: url('/fonts/kalpurush/kalpurush-bold.woff2') format('woff2'); }
        @font-face { font-family:'Kalpurush'; font-weight:700; font-display:swap; src: url('/fonts/kalpurush/kalpurush-bold.woff2') format('woff2'); }
        @font-face { font-family:'Kalpurush'; font-weight:800; font-display:swap; src: url('/fonts/kalpurush/kalpurush-bold.woff2') format('woff2'); }
    </style>
    <style>
        :root{ --print-accent:#222; --print-border:#444; }
        /* Default page setup - can be overridden in @push('print_head') */
        @page{ size:A4 portrait; margin:12mm 12mm 18mm 12mm; }
        *{ box-sizing:border-box; }
        body{ font-family:'Kalpurush', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Liberation Sans', sans-serif; margin:0; color:#000; font-size:14px; line-height:1.4; }
        /* Wrapper simplified to avoid forcing extra blank print page */
        .print-wrapper{display:block;position:relative;}
        .print-main{padding:4px 0 0;position:relative;}
        /* Reserve space for fixed footer to avoid overlap with table rows (screen + print) */
        .print-main{padding-bottom:80px;margin-bottom:0;}
        @media print{ 
            body{margin:0;padding:0;}
            .print-wrapper{position:relative;}
            .print-main{padding-bottom:0 !important;} 
            /* Prevent table rows from breaking across pages */
            table{page-break-after:auto;}
            table tr{page-break-inside:avoid !important;page-break-after:auto;}
            table td{page-break-inside:avoid !important;}
            table thead{display:table-header-group;}
            table tfoot{display:table-footer-group;}
        }
        .print-header{ display:flex; align-items:center; gap:12px; border-bottom:2px solid var(--print-accent); padding:0 0 8px; margin-bottom:4px; position:relative; }
        .print-header .logo img{ width:70px; height:70px; object-fit:contain; }
        .print-header .logo{ position:absolute; left:6px; top:10px; width:0; height:0; overflow:visible; z-index:10; }
        .print-header .center{ flex:1; text-align:center; position:relative; padding-top:0; }
        .school-name{ font-size:28px; font-weight:800; margin:5px 0 0 0; line-height:0.95; }
        .school-address{ font-size:14px; margin:0; font-weight:500; line-height:1; }
        .page-title{ font-size:20px; font-weight:700; margin:0; line-height:1; }
        .page-subtitle{ font-size:15px; font-weight:600; margin:0; line-height:1; }
        /* Fixed highlighted footer style */
        .fixed-footer{position:fixed;left:0;right:0;bottom:0;text-align:center;font-size:12px;font-weight:700;background:#fff7a8;color:#000;padding:5px 10px;border-top:1px solid #333;z-index:9999;-webkit-print-color-adjust:exact;print-color-adjust:exact;color-adjust:exact;}
        @media screen{
            .fixed-footer{display:block;}
        }
        @media print{
            .fixed-footer{position:fixed;bottom:0;left:0;right:0;height:auto;padding:5px 10px;font-size:11px;background:#fff7a8 !important;-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;}
        }
        .fixed-footer .line{display:block;}
        .fixed-footer .meta{font-weight:600;font-size:11px;margin-top:2px;}
        .overlay-tools{position:fixed;top:6px;right:6px;z-index:999;display:flex;gap:8px;background:rgba(255,255,255,0.85);padding:6px 10px;border:1px solid #aaa;border-radius:8px;backdrop-filter:blur(4px);box-shadow:0 2px 4px rgba(0,0,0,0.15);}
        .overlay-tools button,.overlay-tools a{border:1px solid #666;background:#fff;padding:4px 8px;font-size:11px;cursor:pointer;text-decoration:none;color:#000;border-radius:4px;}
        .overlay-tools .active{background:#222;color:#fff;}
        .lang-switch{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;}
        .switch{position:relative;display:inline-block;width:54px;height:24px;}
        .switch input{display:none;}
        .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#bbb;transition:.3s;border-radius:24px;}
        .slider:before{content:'';position:absolute;height:20px;width:20px;left:2px;top:2px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,0.4);}
        input:checked + .slider{background:#1b5e20;}
        input:checked + .slider:before{transform:translateX(30px);}        
        .switch-labels{display:flex;justify-content:space-between;position:absolute;top:0;left:0;width:100%;height:100%;font-size:10px;font-weight:700;color:#111;pointer-events:none;}
        .switch-labels span{flex:1;display:flex;align-items:center;justify-content:center;}
        input:checked ~ .switch-labels span.bn{color:#fff;}
        input:not(:checked) ~ .switch-labels span.en{color:#fff;}
        /* Center watermark logo overlay */
        .logo-overlay{position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);opacity:.06;z-index:0;pointer-events:none;}
        .logo-overlay img{width:420px;max-width:60vw;height:auto;filter:grayscale(40%);}
        @media print{ .no-print, .overlay-tools{ display:none !important; } body{ margin:0; } .print-wrapper{page-break-after:avoid;} }
    </style>
    @stack('print_head')
</head>
<body>
    <?php
        $logoUrl = asset('images/default-logo.png');
        if(isset($school) && $school && $school->logo){
            $candidates = [
                'uploads/schools/'.$school->logo,
                'storage/schools/'.$school->logo,
                'storage/'.$school->logo,
            ];
            foreach($candidates as $c){ if(file_exists(public_path($c))){ $logoUrl = asset($c); break; } }
        }
    ?>
    <div class="print-header">
        <div class="logo">@if($logoUrl)<img src="{{ $logoUrl }}" alt="logo">@endif</div>
        <div class="center">
            @if (\Illuminate\Support\Facades\View::exists('print_common'))
                @include('print_common', ['school' => $school ?? null, 'title' => $printTitle ?? null, 'subtitle' => $printSubtitle ?? null])
            @elseif (\Illuminate\Support\Facades\View::exists('layouts.print_common'))
                @include('layouts.print_common', ['school' => $school ?? null, 'title' => $printTitle ?? null, 'subtitle' => $printSubtitle ?? null])
            @elseif (\Illuminate\Support\Facades\View::exists('partials.print_common'))
                @include('partials.print_common', ['school' => $school ?? null, 'title' => $printTitle ?? null, 'subtitle' => $printSubtitle ?? null])
            @elseif (\Illuminate\Support\Facades\View::exists('common.print_common'))
                @include('common.print_common', ['school' => $school ?? null, 'title' => $printTitle ?? null, 'subtitle' => $printSubtitle ?? null])
            @else
                <h1 class="school-name">{{ $lang==='bn' ? ($school->name_bn ?? $school->name) : ($school->name ?? $school->name_bn) }}</h1>
                @php $addr = $lang==='bn' ? ($school->address_bn ?? $school->address) : ($school->address ?? $school->address_bn); @endphp
                @if($addr)
                    <div class="school-address">{{ $addr }}</div>
                @endif
                @isset($printTitle)<div class="page-title">{{ $printTitle }}</div>@endisset
                @isset($printSubtitle)<div class="page-subtitle">{{ $printSubtitle }}</div>@endisset
            @endif
            @yield('print_header_right')
        </div>
    </div>
    @if($logoUrl)
        <div class="logo-overlay"><img src="{{ $logoUrl }}" alt="logo watermark"></div>
    @endif
    <div class="print-wrapper">
    <div class="overlay-tools no-print">
        @php $baseUrl = url()->current(); $query = request()->except('lang'); @endphp
        <div class="lang-switch">
            <label class="switch">
                <input type="checkbox" id="langToggle" {{ $lang==='bn' ? 'checked' : '' }}>
                <span class="slider"></span>
                <div class="switch-labels"><span class="bn">বাংলা</span><span class="en">EN</span></div>
            </label>
        </div>
        <button onclick="window.print()">Print</button>
        <a href="{{ url()->previous() }}">Back</a>
    </div>
    <script class="no-print">
        (function(){
            var t=document.getElementById('langToggle');
            if(!t) return;
            t.addEventListener('change',function(){
                var lang = t.checked ? 'bn':'en';
                var params = new URLSearchParams(window.location.search);
                params.set('lang',lang);
                window.location.href = window.location.pathname + '?' + params.toString();
            });
        })();
    </script>
    <div class="print-main">
        @yield('content')
    </div>
    @php
        $dtFmt = now()->format('d M Y H:i');
        if($lang==='bn'){
            $digitsBn = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
            $dtFmt = strtr($dtFmt,$digitsBn);
        }
    @endphp
    <div class="fixed-footer">
        <div class="line">{{ $lang==='bn' ? 'Developed by, Md. Abdul Halim | Batighor Computers' : 'Developed by, Md. Abdul Halim | Batighor Computers' }} | https://batighorbd.com </div>
    </div>
    </div>
    @stack('print_scripts')
</body>
</html>
