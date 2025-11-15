<div class="print-footer d-none d-print-block" style="position:fixed; bottom:0; left:0; right:0;">
    <div style="text-align:center; border-top:1px solid #000; padding:4px 0; font-size:10px; background:#fff;">
        <div style="font-weight:600;">Powered by {{ config('app.name') }}</div>
        <div>© {{ date('Y') }} All rights reserved.</div>
        @if(env('APP_ENV') !== 'production')
            <div style="font-size:9px;">Environment: {{ env('APP_ENV') }}</div>
        @endif
        <div style="font-size:9px;">পৃষ্ঠার সমাপ্তি</div>
    </div>
</div>