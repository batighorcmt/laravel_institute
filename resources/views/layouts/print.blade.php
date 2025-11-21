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
        @page{ size:A4; margin:10mm; }
        *{ box-sizing:border-box; }
        body{ font-family:'Kalpurush', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Liberation Sans', sans-serif; margin:0; color:#000; font-size:12px; }
        /* Wrapper simplified to avoid forcing extra blank print page */
        .print-wrapper{display:block;}
        .print-main{padding:4px 0 0;}
        /* Provide minimal bottom padding; large content will naturally flow onto next page */
        .print-main{padding-bottom:30px;}
        .print-header{ display:flex; align-items:center; gap:12px; border-bottom:2px solid var(--print-accent); padding:6px 0 10px; margin-bottom:10px; }
        .print-header .logo img{ width:100px; height:100px; object-fit:contain; }
        .print-header .logo{ flex:0 0 70px; text-align:left; margin-left:60px; }
        .print-header .center{ flex:1; text-align:center; }
        .school-name{ font-size:26px; font-weight:800; margin:0; line-height:1.05; }
        .school-address{ font-size:13px; margin:3px 0 4px; }
        .page-title{ font-size:18px; font-weight:700; margin:4px 0 2px; }
        .page-subtitle{ font-size:14px; font-weight:600; margin:0 0 4px; }
        /* Fixed highlighted footer style (screen + print) */
        .fixed-footer{position:fixed;left:0;right:0;bottom:0;text-align:center;font-size:12px;font-weight:800;background:#fff7a8;color:#000;padding:8px 10px;border-top:2px solid #333;z-index:9999;}
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
        <div class="logo"><img src="{{ $logoUrl }}" alt="logo"></div>
        <div class="center">
            <h1 class="school-name">{{ $lang==='bn' ? ($school->name_bn ?? $school->name) : ($school->name ?? $school->name_bn) }}</h1>
            @php $addr = $lang==='bn' ? ($school->address_bn ?? $school->address) : ($school->address ?? $school->address_bn); @endphp
            @if($addr)
                <div class="school-address">{{ $addr }}</div>
            @endif
            @isset($printTitle)<div class="page-title">{{ $printTitle }}</div>@endisset
            @isset($printSubtitle)<div class="page-subtitle">{{ $printSubtitle }}</div>@endisset
        </div>
        <div class="right">@yield('print_header_right')</div>
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
