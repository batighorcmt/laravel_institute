<x-layout.public :school="$school" :title="'ভর্তি ফিস রশিদ — ' . ($school->name ?? '')">
    @php
        $logo = $school->logo ? asset('storage/'.$school->logo) : asset('images/default-logo.png');
        $amount = (float)($payment->amount ?? 0);
        $txnId = $payment->transaction_id ?? $payment->tran_id ?? $payment->bank_tran_id ?? $payment->ssl_tran_id ?? $payment->id;
        function numWordsEn($n) {
            $words = [0=>"zero",1=>"one",2=>"two",3=>"three",4=>"four",5=>"five",6=>"six",7=>"seven",8=>"eight",9=>"nine",10=>"ten",
                11=>"eleven",12=>"twelve",13=>"thirteen",14=>"fourteen",15=>"fifteen",16=>"sixteen",17=>"seventeen",18=>"eighteen",19=>"nineteen",
                20=>"twenty",30=>"thirty",40=>"forty",50=>"fifty",60=>"sixty",70=>"seventy",80=>"eighty",90=>"ninety"];
            $n = (int)$n;
            if ($n < 20) return $words[$n] ?? '';
            $t = (int)floor($n/10)*10; $u = $n%10;
            return trim(($words[$t] ?? '').($u ? ' '.($words[$u] ?? '') : ''));
        }
        function amountInWordsEn($num) {
            $n = (int)floor($num);
            if ($n === 0) return 'Zero Taka Only.';
            $out = [];
            $units = [[10000000,'Crore'],[100000,'Lakh'],[1000,'Thousand'],[100,'Hundred']];
            foreach ($units as [$div,$label]) {
                if ($n >= $div) {
                    $q = (int)floor($n / $div);
                    $n = $n % $div;
                    $out[] = ucfirst(numWordsEn($q)).' '.$label;
                }
            }
            if ($n > 0) {
                $out[] = ucfirst(numWordsEn($n));
            }
            return trim(implode(' ', $out)).' Taka Only.';
        }
        $amountWords = amountInWordsEn($amount);

        // Prepare class display like: 6 (Six)
        $classRaw = $application->class_name ?? '';
        $classNum = null;
        if (is_numeric($classRaw)) { $classNum = (int)$classRaw; }
        else if (preg_match('/\d+/', (string)$classRaw, $m)) { $classNum = (int)$m[0]; }
        $classDisplay = $classRaw ?: '—';
        if ($classNum && $classNum > 0) {
            $classDisplay = $classNum.' ('.ucfirst(numWordsEn($classNum)).')';
        }
    @endphp
    @push('styles')
    <style>
        body { background:#ffffff; }
        .receipt-container { max-width: 860px; margin: 16px auto; background:#fff; padding: 12px; font-size: 1.2rem; }
        .receipt { border:1px solid #ccc; padding: 12px 24px 20px; margin-bottom: 20px; position: relative; }
        .copy-label { text-align:right; font-weight:700; font-size: 1.1em; }
        .watermark { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); opacity:.06; width:400px; z-index:0; }
        .watermark img { width:100%; }
        .content { position: relative; z-index: 1; }
        .header { display:flex; align-items:center; justify-content:space-between; }
        .logo img { width:64px; height:64px; object-fit:contain; }
        .header-text { text-align:center; flex:1; }
        .header-text h2 { font-size: 42px; font-weight: 800; line-height: 1.2; }
        .header-text p { font-size: 1.05em; }
        .title { font-size: 24px; font-weight:1000; text-align:center; margin:6px 0; border-top:1px dashed #888; border-bottom:1px dashed #888; padding:6px 0; }
        .title strong { color:#000000; padding:2px 10px; border-radius:4px; box-shadow:0 0 0 2px #000000 inset; }
        .row { display:flex; margin:6px 0; }
        .label { width:220px; font-weight:700; font-size: 1.05em; }
        .value { flex:1; border-bottom:1px dotted #aaa; padding:2px 10px; font-size: 1.05em; }
        .footer { display:flex; justify-content:space-between; margin-top: 50px; margin-bottom: 80px; font-weight:700; font-size: 1.1em; }
        .divider { width:100%; text-align:center; position:relative; height:20px; margin:22px 0; }
        .divider::before { content:""; position:absolute; top:50%; left:0; right:0; border-top:2px dashed #aaa; z-index:1; }
        .divider span { background:#fff; padding:0 15px; position:relative; z-index:2; font-size:18px; font-weight:700; color:#555; }
        @media print { .no-print { display:none !important; } .receipt { page-break-inside: avoid; } }
    </style>
    @endpush
    <div class="receipt-container">
        @for($i=0;$i<2;$i++)
            <div class="receipt">
                <div class="copy-label">{{ $i===0 ? 'Office Copy' : 'Student Copy' }}</div>
                <div class="watermark"><img src="{{ $logo }}" alt="Watermark"></div>
                <div class="content">
                    <div class="header">
                        <div class="logo"><img src="{{ $logo }}" alt="Logo"></div>
                        <div class="header-text">
                            <h2 class="m-0">{{ $school->name }}</h2>
                            <p class="m-0">{{ $school->address ?? 'Address not available' }}</p>
                            <p class="m-0">Mobile: {{ $school->phone ?? 'N/A' }} | Email: {{ $school->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="title strong"><strong>Payment Receipt</strong></div>
                    <div class="row"><div class="label">Receipt No :</div><div class="value">{{ $payment->invoice_no ?? $payment->id }}</div> <div class="label">Date :</div><div class="value">{{ optional($payment->created_at)->format('Y-m-d') }}</div></div>
                    <div class="row"><div class="label">Student Name :</div> <div class="value">{{ $application->name_en ?? $application->name_bn }} ({{ $application->app_id }}) </div></div>
                    <div class="row"><div class="label">Class :</div><div class="value">{{ $classDisplay }}</div></div>
                    <div class="row"><div class="label">Amount :</div><div class="value">৳ {{ number_format($amount,2) }}</div></div>
                    <div class="row"><div class="label">In Words :</div><div class="value">{{ $amountWords }}</div></div>
                    <div class="row"><div class="label">Purpose :</div><div class="value">Admission Fee</div></div>
                    <div class="row"><div class="label">Payment Method :</div><div class="value">{{ $payment->payment_method ?? 'Online' }}</div> <div class="label">Transaction ID :</div><div class="value">{{ $txnId }}</div></div>
                    <div class="footer">
                        <div style="margin-left: 40px">Depositor</div>
                        <div style="margin-right: 40px">Receiver</div>
                    </div>
                </div>
            </div>
            @if($i===0)
                <div class="divider"><span>✂</span></div>
            @endif
        @endfor
        <div class="text-center no-print">
            <button onclick="window.print()" class="btn btn-dark btn-sm"><i class="fa-solid fa-print me-1"></i> প্রিন্ট করুন</button>
            <a href="{{ route('admission.preview', [$school->code, $application->app_id]) }}" class="btn btn-outline-primary btn-sm ms-2" target="_blank">
                <i class="fa-solid fa-eye me-1"></i> প্রিভিউ
            </a>
        </div>
    </div>
</x-layout.public>
